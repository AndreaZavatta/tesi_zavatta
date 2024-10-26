import json
import pymysql
import sys

# Load database configuration from file
with open('../db_config.json', 'r') as config_file:
    db_config = json.load(config_file)

# Check if file path argument is provided
if len(sys.argv) < 2:
    print("No file path provided.")
    sys.exit(1)

# Load JSON data from the file
file_path = sys.argv[1]
print(f"Loading JSON data from file: {file_path}")
try:
    with open(file_path, 'r', encoding='utf-8') as file:
        json_data = json.load(file)
    print("JSON data loaded successfully.")
except json.JSONDecodeError as e:
    print(f"Failed to parse JSON file: {e}")
    sys.exit(1)
except Exception as e:
    print(f"Unexpected error while loading JSON: {e}")
    sys.exit(1)

# Connect to MySQL server using configuration details
print("Connecting to MySQL server...")
try:
    conn = pymysql.connect(
        host=db_config['host'],
        user=db_config['user'],
        password=db_config['password'],
        database=db_config['database_mappa']
    )
    cursor = conn.cursor()
    print("Connected successfully.")
except pymysql.MySQLError as e:
    print(f"MySQL connection error: {e}")
    sys.exit(1)
except Exception as e:
    print(f"Unexpected error: {e}")
    sys.exit(1)

# Ensure tables exist
cursor.execute("""
CREATE TABLE IF NOT EXISTS politici (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nominativo VARCHAR(255) UNIQUE,
    gruppo_politico VARCHAR(255)
);
""")
cursor.execute("""
CREATE TABLE IF NOT EXISTS sedute (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_seduta DATE UNIQUE
);
""")
cursor.execute("""
CREATE TABLE IF NOT EXISTS presenze (
    id INT AUTO_INCREMENT PRIMARY KEY,
    politico_id INT,
    seduta_id INT,
    presenza VARCHAR(255),
    FOREIGN KEY (politico_id) REFERENCES politici(id),
    FOREIGN KEY (seduta_id) REFERENCES sedute(id)
);
""")
cursor.execute("""
CREATE TABLE IF NOT EXISTS votazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presenza_id INT,
    num_votazioni INT,
    percentuale_presenza_alle_votazioni FLOAT,
    FOREIGN KEY (presenza_id) REFERENCES presenze(id)
);
""")
print("Tables are set up.")

# Process JSON data with commit after each record
print(f"Loaded {len(json_data)} records from JSON data.")
for i, record in enumerate(json_data, start=1):
    print(f"Processing record {i}/{len(json_data)}...")

    nominativo = record['nominativo'].strip()
    gruppo_politico = record['gruppo_politico'].strip()
    data_seduta = record['data_seduta']
    presenza = record['presenza']
    num_votazioni = record['num_votazioni']
    percentuale_presenza_alle_votazioni = record['percentuale_presenza_alle_votazioni']
    
    # Insert or fetch politician ID
    cursor.execute("SELECT id FROM politici WHERE nominativo = %s", (nominativo,))
    politico_id = cursor.fetchone()
    if not politico_id:
        cursor.execute("INSERT INTO politici (nominativo, gruppo_politico) VALUES (%s, %s)", (nominativo, gruppo_politico))
        politico_id = cursor.lastrowid
    else:
        politico_id = politico_id[0]

    # Insert or fetch session ID
    cursor.execute("SELECT id FROM sedute WHERE data_seduta = %s", (data_seduta,))
    seduta_id = cursor.fetchone()
    if not seduta_id:
        cursor.execute("INSERT INTO sedute (data_seduta) VALUES (%s)", (data_seduta,))
        seduta_id = cursor.lastrowid
    else:
        seduta_id = seduta_id[0]

    # Insert presence record
    cursor.execute("""
    INSERT INTO presenze (politico_id, seduta_id, presenza)
    VALUES (%s, %s, %s)
    """, (politico_id, seduta_id, presenza))
    presenza_id = cursor.lastrowid

    # Insert voting details
    cursor.execute("""
    INSERT INTO votazioni (presenza_id, num_votazioni, percentuale_presenza_alle_votazioni)
    VALUES (%s, %s, %s)
    """, (presenza_id, num_votazioni, percentuale_presenza_alle_votazioni))

    # Commit after each record
    conn.commit()
    print(f"Record {i} committed successfully.\n")

print("Data loading complete. Connection closing.")
conn.close()
print("Connection closed.")
