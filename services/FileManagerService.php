<?php

namespace services;

use utils\FTPService;

class FileManagerService
{	
	public function __construct(
		FTPService $ftpService,
		string $localPath,
		string $ftpPath
	) {
		$this->ftpService = $ftpService;
		$this->localPath = $localPath;
		$this->ftpPath = $ftpPath;
	}
	
	/**
	 * @throws \Exception 
	 */
	public function connectToFTP(string $server, string $userName, string $password)
	{
		$this->ftpService->connect($server);
		$this->ftpService->login($userName, $password);
	}

	/**
	 * @throws \Exception 
	 */
	public function closeConnectionToFTP()
	{
		$this->ftpService->closeConnection();
	}

    public function localDirectoryExists(): bool
    {
        return is_dir($this->localPath);
    }

	/**
	 * @throws \Exception 
	 */
    public function createLocalDirectory()
    {
		if (!mkdir($this->localPath)) {
			throw new \Exception("Couldn't create local directory");
		}
	}
	
	public function downloadCSVFileForMonth(int $year, int $month)
	{
		$fileNames = $this->ftpService->getCSVFileNamesForMonth($this->ftpPath, 2016, 5);

		foreach ($fileNames as $fileName) {
			try {
				$this->ftpService->getFile($this->localPath . basename($fileName), $fileName);
			} catch (Exception $e) {
				echo $e->getMessage() . "\n";
			}
		}
	}

	/**
	 * @throws \Exception 
	 */
	public function downloadAdvertiserFile(string $advertiserFileName)
	{
		$this->ftpService->getFile($this->localPath . $advertiserFileName, $this->ftpPath . $advertiserFileName);
	}

	public function cleanLocalDirectory()
	{
		$files = glob($this->localPath . '*'); 
		foreach ($files as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
	}
}