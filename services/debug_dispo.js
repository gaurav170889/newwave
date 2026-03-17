
import mysql from 'mysql2/promise';
import 'dotenv/config';

const db = mysql.createPool({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME
});

async function run() {
    const [rows] = await db.execute('SELECT * FROM dialer_disposition_master');
    console.log(JSON.stringify(rows, null, 2));
    process.exit();
}

run();
