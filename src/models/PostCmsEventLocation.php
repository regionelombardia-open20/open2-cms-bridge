<?php

/**
 * Aria S.p.A.
 * OPEN 2.0
 *
 *
 * @package    Open20Package
 * @category   CategoryName
 */

namespace open20\cmsbridge\models;

use open20\cmsbridge\utility\CmsObject;

class PostCmsEventLocation extends CmsObject
{
    public $location_name;
    public $location_description;
    public $location_address;
    public $place_response;
    public $place_type;
    public $country;
    public $region;
    public $province;
    public $postal_code;
    public $city;
    public $address;
    public $street_number;
    public $latitude;
    public $longitude;

}