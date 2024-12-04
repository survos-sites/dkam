<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileDriver;

class MyMetadataDriverService extends FileDriver
{
    /**
     * {@inheritDoc}
     */
    protected $_fileExtension = '.dcm.yml';

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        dd($className, $metadata);
        $data = $this->_loadMappingFile($file);

        // populate ClassMetadata instance from $data
    }


    protected function loadMappingFile(string $file)
    {
        dd($file);
        // TODO: Implement loadMappingFile() method.
    }
}
