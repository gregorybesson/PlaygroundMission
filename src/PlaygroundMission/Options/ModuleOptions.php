<?php

namespace PlaygroundMission\Options;

use PlaygroundGame\Options\ModuleOptions as GameModuleOptions;
use Zend\Stdlib\AbstractOptions;

class ModuleOptions extends GameModuleOptions
{
    
        /**
     * drive path to game media files
     */
    protected $media_path = 'public/media/mission';

    /**
     * url path to game media files
     */
    protected $media_url = 'media/mission';

     /**
     * @var string
     */
    protected $gameEntityClass = 'Mission\Entity\Game';

    /**
     * Set game entity class name
     *
     * @param $gameEntityClass
     * @return ModuleOptions
     */
    public function setGameEntityClass($gameEntityClass)
    {
        $this->gameEntityClass = $gameEntityClass;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGameEntityClass()
    {
        return $this->gameEntityClass;
    }

    /*
     * @return \PlaygroundDesign\Options\ModuleOptions
     */
    public function setMediaPath($media_path)
    {
        $this->media_path = $media_path;

        return $this;
    }

    /**
     * @return string
     */
    public function getMediaPath()
    {
        return $this->media_path;
    }

    /**
     *
     * @param  string $media_url
     * @return \PlaygroundDesign\Options\ModuleOptions
     */
    public function setMediaUrl($media_url)
    {
        $this->media_url = $media_url;

        return $this;
    }

    /**
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->media_url;
    }
}