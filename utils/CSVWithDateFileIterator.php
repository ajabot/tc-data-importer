<?php
namespace utils;

class CSVWithDateFileIterator extends \FilterIterator
{	
	public function __construct(\DirectoryIterator $directory)
	{
		parent::__construct($directory);
	}

	public function accept(): bool
	{
		$file = parent::current();
		return (
			!$file->isDot() &&
			$file->getExtension() == 'csv' &&
			preg_match('/\d{4}-\d{2}-\d{2}/', $file->getFileName()) > 0
		);
	}
}