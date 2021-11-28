<?php


namespace gem\interfaces\services\fileZipper;


interface fileZipperInterface
{

	/**
	 * @param array $files
	 * @return fileZipperInterface
	 * Must include array keys: output_name, source_name
	 * output_name = Final filename for files to be zipped
	 * source_name = Filename of source file, without directory
	 */
	public function setFiles( array $files ): fileZipperInterface;



	/**
	 * @param string $name
	 * @return fileZipperInterface
	 * Name of output zip
	 */
	public function setZipName( string $name ): fileZipperInterface;

}