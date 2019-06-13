# Data Importer

This is the solution for a technical exercice

## How to run it

1.  **Make sure the database is setup**
    if your database doesn't contain the tables `zz__yashi_cgn`, `zz__yashi_cgn_data`, `zz__yashi_order`, `zz__yashi_order_data`,`zz__yashi_creative` and `zz__yashi_creative_data`, make sure to run the SQL script provided with the exercise instructions.
    
2.  **Create your config file**
    Use the example config file to create your config file and then update it with your database and FTP information
    ```sh
    cp config.example config.php
    vim config.php
    ```

3.  **Run the script**

    Now that everything is set up, you can import you data by running

    ```sh
    php import_data.php
    ```