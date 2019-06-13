<?php
namespace utils;

class FTPService
{	
	private $ftpStream;

	/**
	 * @throws \Exception 
	 */
	public function connect(string $server)
	{
		$ftpStream = ftp_connect($server);

		if (!$ftpStream) {
			throw new \Exception("Unable to connect to FTP server");
		}

		$this->ftpStream = $ftpStream;
	}

	/**
	 * @throws \Exception 
	 */
	public function closeConnection()
	{
		if (!ftp_close($this->ftpStream)) {
			throw new \Exception("Unable to close connection to FTP server");
		}
	}

	/**
	 * @throws \Exception 
	 */
	public function login(string $userName, string $password)
	{
		if(!ftp_login($this->ftpStream, $userName, $password)) {
			throw new \Exception("Unable to log in as $userName");
		}
	}

	/**
	 * @throws \Exception 
	 */
	public function getFile(string $localFileName, string $ftpFileName)
	{
		if (!ftp_get($this->ftpStream, $localFileName, $ftpFileName, FTP_BINARY)) {
			throw new \Exception("Couldn't download $ftpFileName to $localFileName");
		}
	}

	public function getCSVFileNamesForMonth(string $directory, int $year, int $month): array
	{
		$year = sprintf('%04d', $year);
		$month = sprintf('%02d', $month);
		$directoryFileNames = ftp_nlist($this->ftpStream , $directory);
		$fileNames = array_filter($directoryFileNames, function ($fileName) use ($year, $month) {
			return preg_match('/'. $year . '-' . $month . '-\d{2}.*\.csv$/', $fileName) > 0;
		});

		return $fileNames;
	}
}