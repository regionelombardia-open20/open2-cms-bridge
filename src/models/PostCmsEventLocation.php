<?php
/**
 * Aria S.p.A.
 * OPEN 2.0
 *
 * @package   amos\cmsbridge
 * @author    Aria S.p.A. <protocollo@pec.ariaspa.it>
 * @license   GNU General Public License v3.0 <https://www.gnu.org/licenses/gpl-3.0.html>
 * @link      https://www.ariaspa.it/
 *
 * This file is part of OPEN 2.0.
 *
 * OPEN 2.0 is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OPEN 2.0 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OPEN 2.0. If not, see <https://www.gnu.org/licenses/>.
 */

namespace amos\cmsbridge\models;

use amos\cmsbridge\utility\CmsObject;

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