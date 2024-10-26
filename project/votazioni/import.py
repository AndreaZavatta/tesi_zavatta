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
    print("Connecting to MySQL server...")
    conn = pymysql.connect(
        host=db_config['host'],
        user=db_config['user'],
        password=db_config['password'],
    )
    cursor = conn.cursor()
    print("Connected successfully.")
except pymysql.MySQLError as e:
    print(f"MySQL connection error: {e}")
    sys.exit(1)
except Exception as e:
    print(f"Unexpected error: {e}")
    sys.exit(1)


# Create database if not exists
print("Creating or using database 'prova'...")
cursor.execute("CREATE DATABASE IF NOT EXISTS "+db_config['database_mappa'])
cursor.execute("USE "+db_config['database_mappa'])

# Create tables
print("Creating tables if they do not exist...")
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

# Process JSON data
print(f"Loaded {len(json_data)} records from JSON data.")

for i, record in enumerate(json_data, start=1):
    print(f"Processing record {i}/{len(json_data)}...")

    nominativo = record['nominativo'].strip()
    gruppo_politico = record['gruppo_politico'].strip()
    data_seduta = record['data_seduta']
    presenza = record['presenza']
    num_votazioni = record['num_votazioni']
    percentuale_presenza_alle_votazioni = record['percentuale_presenza_alle_votazioni']
    
    # Insert politician if not exists
    print(f"Checking if politician '{nominativo}' exists...")
    cursor.execute("SELECT id FROM politici WHERE nominativo = %s", (nominativo,))
    politico_id = cursor.fetchone()
    if not politico_id:
        print(f"Inserting politician '{nominativo}' into 'politici' table...")
        cursor.execute("INSERT INTO politici (nominativo, gruppo_politico) VALUES (%s, %s)", (nominativo, gruppo_politico))
        politico_id = cursor.lastrowid
    else:
        politico_id = politico_id[0]
    print(f"Politician ID: {politico_id}")

    # Insert session date if not exists
    print(f"Checking if session date '{data_seduta}' exists...")
    cursor.execute("SELECT id FROM sedute WHERE data_seduta = %s", (data_seduta,))
    seduta_id = cursor.fetchone()
    if not seduta_id:
        print(f"Inserting session date '{data_seduta}' into 'sedute' table...")
        cursor.execute("INSERT INTO sedute (data_seduta) VALUES (%s)", (data_seduta,))
        seduta_id = cursor.lastrowid
    else:
        seduta_id = seduta_id[0]
    print(f"Session ID: {seduta_id}")

    # Insert presence record
    print(f"Inserting presence record for politician ID {politico_id} and session ID {seduta_id}...")
    cursor.execute("""
    INSERT INTO presenze (politico_id, seduta_id, presenza)
    VALUES (%s, %s, %s)
    """, (politico_id, seduta_id, presenza))
    presenza_id = cursor.lastrowid
    print(f"Presence ID: {presenza_id}")

    # Insert voting details
    print(f"Inserting voting details for presence ID {presenza_id}...")
    cursor.execute("""
    INSERT INTO votazioni (presenza_id, num_votazioni, percentuale_presenza_alle_votazioni)
    VALUES (%s, %s, %s)
    """, (presenza_id, num_votazioni, percentuale_presenza_alle_votazioni))
    print("Voting details inserted.\n")

# Commit changes and close the connection
print("Committing changes to the database...")
conn.commit()
conn.close()
print("Connection closed. Data successfully inserted into the normalized tables.")
