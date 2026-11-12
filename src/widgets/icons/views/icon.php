<?php

/**
 * Aria S.p.A.
 * OPEN 2.0
 *
 * @package   open20\amos\core\widget\views
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

/**
 * @var $this \yii\web\View
 * @var $widget \open20\amos\core\widget\WidgetIcon
 * @var $asset \yii\web\AssetBundle
 */

$moduleCms = Yii::$app->getModule('cmsbridge');
$moduleCms->frontendUrl = !empty($moduleCms->frontendUrl)? $moduleCms->frontendUrl : Yii::$app->params['platform']['frontendUrl'];


$classSpanStr = @join(' ', $widget->classSpan);
$classSpanLi = @join(' ', $widget->classLi);
$classSpanA = @join(' ', $widget->classA);
$className = $widget::className();

$userAgent = (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/') > -1 || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) ? 'ie' : '';

$url = is_array($widget->url) ? \yii\helpers\Url::to($widget->url) : $widget->url;
$target = ((strlen($widget->targetUrl) > 0) ? 'target="' . $widget->targetUrl . '" ' : '');
$dataModule = $widget->moduleName;
use open20\amos\core\icons\AmosIcons;
use open20\amos\mobile\bridge\modules\v1\models\User as TokenUser;

$user = TokenUser::findOne(Yii::$app->user->id);
$token = $user->refreshAccessToken('webcms', 'cms')->access_token;
$url_front = $moduleCms->frontendUrl . '/admin/login/login-amos';
$base_url_front = $moduleCms->frontendUrl;
$admin_url_front = $moduleCms->frontendUrl . '/admin';
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
$script = <<< JS
window.jCallback = function (data) {
     //let newTab = window.open();
	 window.location.href = '{$admin_url_front}';
    };

    $('#cms_bridge_url').click(function() {

        var csrf = '{$csrfToken}';

        $.ajax({
		cache: false,
                timeout:10000,
                type: "POST",
                url: "{$url_front}",
		jsonp : false,
    		jsonpCallback: 'jCallback',
		crossDomain: true,
                data: { 'secure_token' : '{$token}'},
                contentType: "application/json; charset=utf-8",
		async: false,
                dataType: "jsonp",
                success: function (data) {
                   //do something
                   console.log("working");
                },
                error: function (request, status, error) {

                    //do something else
                    console.log(error);

                }
            });

    });

JS;
$this->registerJs($script);
?>

<div class="item-widget col-custom" data-code="<?= $className ?>">
    <?php if (strlen($url)): ?>
    <a id="cms_bridge_url" data-module = "<?=$dataModule?>" class="<?=$classSpanA?>" href="#" <?= $target ?>title="<?= $widget->description ?>" role="menuitem" class="sortableOpt1" <?= $widget->dataPjaxZero ?> <?= $widget->attributes ?>>
        <?php endif; ?>
        <?php if (strlen($url) && ($widget->targetUrl == '_blank')): ?>
            <span class="sr-only"><?= Yii::t('amoscore', 'Questo link verrà aperto in una nuova pagina') ?></span>
        <?php endif; ?>
        <span class="badge"><?= $widget->bulletCount ? $widget->bulletCount : '' ?></span>
        <span class="<?= $classSpanStr ?>">
            <?= \open20\amos\core\icons\AmosIcons::show($widget->icon, [], $widget->iconFramework) ?>
            <!--span class="svg-container">
                <svg title="< ?= $widget->description ?>" role="img" class="svg-content">
                  <use xlink:href="< ?= $asset->baseUrl ?>/svg/icone< ?= vv ?>.svg#< ?= $widget->icon ?>"></use>
                </svg>
            </span-->
        <span class="icon-dashboard-name pluginName"><?= $widget->label ?></span>
    </span>
        <?php if (strlen($url)): ?>
    </a>
<?php endif; ?>
</div>
