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

namespace amos\cmsbridge\widgets\icons;

use open20\amos\core\views\assets\AmosCoreAsset;
use open20\amos\core\widget\WidgetAbstract;
use open20\amos\core\widget\WidgetIcon;
use open20\amos\layout\assets\BaseAsset;
use Yii;
use yii\helpers\ArrayHelper;

class WidgetIconCmsDashboard extends WidgetIcon {

    /**
     * @var Module $moduleCms
     */
    private $moduleCms = null;
       
    public function init() {
        parent::init();

        $this->moduleCms = Yii::$app->getModule('cmsbridge');
        $this->moduleCms->frontendUrl = Yii::$app->params['platform']['frontendUrl']; 
        
        $this->setLabel(\Yii::t('amos\cmsbridge\widgets\icons', 'Cms'));
        $this->setDescription(Yii::t('amos\cmsbridge\widgets\icons', 'Cms'));

        $this->setIcon('linmodulo');
        //$this->setIconFramework();

        /**
         * link to frontend
         * check params from platform/backend/config/params.php
         */
        // get params from platform/common/config/params-local.php
        $this->setUrl($this->moduleCms->frontendUrl . '/admin');
        
        $this->setCode('Cms');
        $this->setModuleName('Cms');
        $this->setNamespace(__CLASS__);
        //  $this->setBulletCount($this->getBulletCountChildWidgets());
        $this->setClassSpan(ArrayHelper::merge($this->getClassSpan(), [
                    'bk-backgroundIcon',
                    'color-primary'
        ]));
    }

    /**
     *
     * @return type
     */
    public function getHtml()
    {
        $controller = \Yii::$app->controller;
        $moduleL = \Yii::$app->getModule('layout');

        if (!empty($moduleL)) {
            $assetBundle = BaseAsset::register($controller->getView());
        } else {
            $assetBundle = AmosCoreAsset::register($controller->getView());
        }

        $view = '@vendor/amos/cmsbridge/src/widgets/icons/views/icon';
        if ($this->getEngine() == WidgetAbstract::ENGINE_ROWS) {
            $view = '@vendor/amos/cmsbridge/src/widgets/icons/views/icon_rows';
        }

        return $this->render(
            $view,
            [
                'asset' => $assetBundle,
                'widget' => $this
            ]
        );
    }

}
