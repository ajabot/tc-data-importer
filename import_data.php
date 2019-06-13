<?php
require_once('./autoload.php');
$config = include('config.php');

use services\FileManagerService;
use services\yashi\CampaignService;
use services\yashi\CreativeService;
use services\yashi\OrderService;
use services\yashi\CSVImportService;
use utils\CSVWithDateFileIterator;
use utils\FTPService;

$fileManager = new FileManagerService(
    new FTPService(),
    $config['yashi']['files']['path'],
    $config['yashi']['ftp']['path']
);

echo "Step 1: Checking local directory " . $config['yashi']['files']['path'] . "\n";

if (!$fileManager->localDirectoryExists()) {
    echo "No yashi folder found, creating folder\n";
    try {
        $fileManager->createLocalDirectory();
    } catch (\Exception $e) {
        echo "$e->getMessage()\n";
        //exit with an error code
        exit(1);
    }
}

echo "Step 2: Cleaning local directory \n";

$fileManager->cleanLocalDirectory();
$fileManager->connectToFTP(
    $config['yashi']['ftp']['host'],
    $config['yashi']['ftp']['user'],
    $config['yashi']['ftp']['password']
);

echo "Step 3: Downloading files from FTP server \n";
//We only focus on the month of May for the exercice
$fileManager->downloadCSVFileForMonth(2016, 5);

try {
    $fileManager->downloadAdvertiserFile($config['yashi']['files']['advertiserFilename']);
} catch (Exception $e) {
    echo "Couldn't get advertisers file \n";
    exit(1);
}

$fileManager->closeConnectionToFTP();

try {
    $connection = new PDO(
        'mysql:dbname=' . $config['yashi']['database']['databaseName'] . ';host=' . $config['yashi']['database']['host'],
        $config['yashi']['database']['user'],
        $config['yashi']['database']['password']
    );
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage() . "\n";
    exit(1);
}

$importService = new CSVImportService(
    $connection,
    new CampaignService($connection),
    new OrderService($connection),
    new CreativeService($connection)
);

$importService->setAdvertisersFromFile($config['yashi']['files']['path'] . $config['yashi']['files']['advertiserFilename']);
$directory = new DirectoryIterator($config['yashi']['files']['path']);

echo "Step 4: Saving files to database\n";

foreach (new CSVWithDateFileIterator($directory) as $file) {
    echo "Processing " . $file->getFileName() . "\n";
    try {
        $importService->importFile($file->getPathname());
    } catch (\Exception $e){
        echo "Error while processing " . $file->getFileName() . ": " . $e->getMessage() . " (no data saved)\n";
    }
}

echo "Step 5: Cleaning local directory\n";
$fileManager->cleanLocalDirectory();
echo "Done\n";
// everything went well, we exit with a code 0
exit(0);