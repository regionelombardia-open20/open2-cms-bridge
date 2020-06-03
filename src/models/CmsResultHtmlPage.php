<?php

/**
 * Aria S.p.A.
 * OPEN 2.0
 *
 *
 * @package    Open20Package
 * @category   CategoryName
 */
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace open20\cmsbridge\models;

use open20\cmsbridge\utility\CmsObject;

/**
 * Description of CmsResultHtmlPage
 *
 * @author stefano
 */
class CmsResultHtmlPage extends CmsObject implements CmsResultPage
{
    public $status;
    public $html;

    /**
     *
     * @param mixed $value
     */
    public function build($value)
    {
        $this->html = $value;
    }
}