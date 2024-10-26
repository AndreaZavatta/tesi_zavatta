const express = require('express');
const mysql = require('mysql');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

// Load database configuration from db_config.json
const configPath = path.join(__dirname, '/../db_config.json'); // Adjust the path as needed
let dbConfig;

try {
  const configData = fs.readFileSync(configPath, 'utf8');
  dbConfig = JSON.parse(configData);
} catch (error) {
  console.error('Error reading db_config.json:', error);
  process.exit(1); // Exit if config can't be read
}

// Initialize the app
const app = express();
app.use(cors());  // Enable CORS for all requests

// Create a MySQL connection using data from db_config.json
const connection = mysql.createConnection({
  host: dbConfig.host,
  user: dbConfig.user,
  password: dbConfig.password,
  database: dbConfig.database, // assuming 'votazioni' is specified as 'database_votazioni' in db_config.json
  port: dbConfig.port || 3306  // Default to 3306 if port is not specified
});

// Connect to the database
connection.connect(err => {
  if (err) {
    console.error('Error connecting to the database:', err);
    return;
  }
  console.log('Connected to the MySQL database');
});

// Helper function to format dates
function formatDate(isoDate) {
  const date = new Date(isoDate);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-based
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

// Define a route to fetch all data from the joined tables
app.get('/api/data', (req, res) => {
  const sql = `
    SELECT 
      p.nominativo,
      p.gruppo_politico,
      s.data_seduta,
      pr.presenza,
      v.num_votazioni,
      v.percentuale_presenza_alle_votazioni
    FROM 
      presenze AS pr
    JOIN 
      politici AS p ON pr.politico_id = p.id
    JOIN 
      sedute AS s ON pr.seduta_id = s.id
    JOIN 
      votazioni AS v ON v.presenza_id = pr.id
  `;
  
  connection.query(sql, (err, results) => {
    if (err) {
      return res.status(500).json({ error: 'Error fetching data from database' });
    }

    // Format the date before sending the results
    const formattedResults = results.map(row => ({
      ...row,
      data_seduta: formatDate(row.data_seduta)  // Format the data_seduta field
    }));

    res.json(formattedResults);  // Send the formatted data as JSON
  });
});

// Define a new route to fetch data for a specific session day (seduta)
app.get('/api/seduta', (req, res) => {
  const sedDay = req.query.s;

  if (!sedDay) {
    return res.status(400).json({ error: 'Missing sedDay query parameter' });
  }

  const sql = `
    SELECT 
      p.nominativo,
      p.gruppo_politico,
      s.data_seduta,
      pr.presenza,
      v.num_votazioni,
      v.percentuale_presenza_alle_votazioni
    FROM 
      presenze AS pr
    JOIN 
      politici AS p ON pr.politico_id = p.id
    JOIN 
      sedute AS s ON pr.seduta_id = s.id
    JOIN 
      votazioni AS v ON v.presenza_id = pr.id
    WHERE 
      s.data_seduta = ?
  `;
  
  connection.query(sql, [sedDay], (err, results) => {
    if (err) {
      return res.status(500).json({ error: 'Error fetching data for the specified seduta' });
    }

    // Format the date before sending the results
    const formattedResults = results.map(row => ({
      ...row,
      data_seduta: formatDate(row.data_seduta)  // Format the data_seduta field
    }));

    res.json(formattedResults);  // Send the formatted data as JSON
  });
});

// Start the server
const PORT = 3000;
app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});
