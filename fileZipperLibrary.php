<?php


namespace gem\services\fileZipper;

use gem\exceptions\fileZipper\fileZipperException;

class fileZipper
{

	protected $zipDir;
	protected $zipName;
	protected $fileDir;
	protected $files = [];



	/**
	 * fileZipper constructor.
	 */
	public function __construct()
	{
		$this->zip = new \ZipArchive();
		$this->zipName = uniqid() . ".zip";
	}



	public function create()
	{
		if ($this->zip->open($this->zipDir . $this->zipName, \ZipArchive::CREATE)!==TRUE) {
			throw new fileZipperException("Cannot set zip file");
		}

		if(!$this->files)
			throw new fileZipperException("No source files have been added");

		foreach( $this->files as $file ) {
			$this->zip->addFile($this->fileDir . $file['source_name'],"/{$file['output_name']}");

		}

		if( count($this->files) !== $this->zip->numFiles)
			throw new fileZipperException("Not all files have been added");

/*		echo "numfiles: " . $this->zip->numFiles . "\n";
		echo "tot arr:" . count($this->files) . "\n";
		echo "status:" . $this->zip->status . "\n";*/

		if(!$this->zip->close())
			throw new fileZipperException("Zip build fail");


		return [
			'location' => $this->zipDir . $this->zipName,
			'name' => $this->zipName
		];

	}
}