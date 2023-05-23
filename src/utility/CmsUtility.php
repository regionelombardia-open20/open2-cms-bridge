<?php

namespace amos\cmsbridge\utility;

use amos\cmsbridge\models\CmsResultCreatePage;
use amos\cmsbridge\models\CmsResultFormat;
use amos\cmsbridge\models\CmsResultHtmlPage;
use amos\cmsbridge\models\CmsResultPage;
use open20\luya\frontend\api\models\PostCmsCreatePage;
use open20\amos\mobile\bridge\modules\v1\models\User as TokenUser;
use Exception;
use luya\admin\models\Lang;
use luya\cms\admin\helpers\MenuHelper;
use open20\luya\admin\module\models\NavItem;
use open20\luya\admin\module\models\Nav;
use open20\luya\frontend\api\utility\CmsLandigBuilder;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\log\Logger;
use yii\web\Response;

class CmsUtility
{
    protected $client;
    protected $loginCookies       = null;
    private static $TKS_TITLE     = "Tks Page ";
    private static $WAITING_TITLE = "Waiting Page ";
    private static $ALREADY_TITLE = "Already Present Page ";

    /**
     * @var Module $moduleCms
     */
    protected $moduleCms = null;

    public function __construct()
    {
        $this->client    = new Client();
        $this->moduleCms = Yii::$app->getModule('cmsbridge');
    }

    /**
     * Login on cms admin tool.
     *
     * @return integer
     */
    public function loginCms()
    {
        $user_id = 0;

        try {
            $user      = TokenUser::findOne(Yii::$app->user->id);
            $token     = $user->refreshAccessToken('webcms', 'cms')->access_token;
            $url_front = $this->moduleCms->frontendUrl.'/admin/login/login-amos';

            $response = $this->client->createRequest()
                ->setMethod('GET')
                ->setUrl($url_front)
                ->setData(['secure_token' => $token])
                ->send();

            if ($response->isOk) {
                $values             = $this->decodeLoginData($response->content);
                $user_id            = $values['user_id'];
                $this->loginCookies = clone $response->cookies;
            }
        } catch (Exception $ex) {
            Yii::getLogger()->log($ex->getTraceAsString(), Logger::LEVEL_ERROR);
        }

        return $user_id;
    }

    /**
     *
     * @param string $data
     * @return array
     */
    private function decodeLoginData($data)
    {
        try {
            $data = str_replace('jCallback', '', $data);
            $data = str_replace('(', '', $data);
            $data = str_replace(')', '', $data);
            $data = str_replace('\'', '"', $data);
            return Json::decode($data);
        } catch (Exception $ex) {
            return [];
        }
    }

    /**
     *
     * @param PostCmsCreatePage $page
     * @param string $url
     * @return CmsResultCreatePage
     */
    public function createCmsPage(PostCmsCreatePage $postCmsPage, $url = null): CmsResultCreatePage
    {
        $result                      = new CmsResultCreatePage();
        $request                     = Yii::$app->request;
        \Yii::$app->response->format = Response::FORMAT_JSON;
        try {

            $this->menuFlush();
            // $postCmsPage = new PostCmsCreatePage();
            if (!is_null($postCmsPage)) {
                if ($postCmsPage->validate()) {
                    \open20\luya\admin\module\Module::setBackendUserId($postCmsPage->cms_user_id);
                    $model = $this->createPage($postCmsPage);
                    if (is_null($model)) {
                        \Yii::$app->response->statusCode = 422;
                    } else {
                        $result->nav_id      = $model->id;
                        $result->preview_url = $model->getPreviewUrl();
                        $postCmsPage->nav_id = $model->id;

                        $landing = new \open20\luya\frontend\api\utility\CmsLandigBuilder([
                            ], $postCmsPage);
                        $data    = $landing->getDataFromTemplate($postCmsPage->from_draft_id);
                        if (!is_null($data)) {
                            $this->already_present_template_page_id = $data->already_present_template_page_id;
                            $this->tks_template_page_id             = $data->tks_template_page_id;
                            $this->waiting_template_page_id         = $data->waiting_template_page_id;
                        }


                        $modeltksP = $this->createTksPage($postCmsPage);
                        if (!is_null($modeltksP)) {
                            $postCmsPage->form_landing->nav_id_tks_page = $modeltksP->id;
                            $result->nav_id_tks_page                    = $modeltksP->id;
                        }
                        $modeltksP = $this->createWaitingPage($postCmsPage);
                        if (!is_null($modeltksP)) {
                            $postCmsPage->form_landing->nav_id_wating_page = $modeltksP->id;
                            $result->nav_id_wating_page                    = $modeltksP->id;
                        }
                        $modeltksP = $this->createAlreadyPage($postCmsPage);
                        if (!is_null($modeltksP)) {
                            $postCmsPage->form_landing->nav_id_already_present_page = $modeltksP->id;
                            $result->nav_id_already_present_page                    = $modeltksP->id;
                        }

                        $landing->buidTks();
                        $landing->buildWaiting();
                        $landing->buildAlready();
                        $landing->build();
                    }
                }
            }
            $result->status = 1;
        } catch (Exception $e) {
            $result->status = false;
        }

        return $result;
    }

    /**
     *
     * @param PostCmsCreatePage $postCmsPage
     * @return Nav
     */
    protected function createAlreadyPage(PostCmsCreatePage $postCmsPage)
    {
        $ret = null;
        if (!empty($this->already_present_template_page_id)) {
            $postCmsPageAlready                = clone $postCmsPage;
            $postCmsPageAlready->use_draft     = true;
            $postCmsPageAlready->from_draft_id = $this->already_present_template_page_id;
            $postCmsPageAlready->title         = static::$ALREADY_TITLE.$postCmsPageAlready->title;
            $postCmsPageAlready->alias         = static::$ALREADY_TITLE.$postCmsPageAlready->alias;

            $ret = $this->createPage($postCmsPageAlready);
        }
        return $ret;
    }

    /**
     *
     * @param PostCmsCreatePage $postCmsPage
     * @return Nav
     */
    protected function createWaitingPage(PostCmsCreatePage $postCmsPage)
    {
        $ret = null;

        if (!empty($this->waiting_template_page_id)) {
            $postCmsPageWait                = clone $postCmsPage;
            $postCmsPageWait->use_draft     = true;
            $postCmsPageWait->from_draft_id = $this->waiting_template_page_id;
            $postCmsPageWait->title         = static::$WAITING_TITLE.$postCmsPageWait->title;
            $postCmsPageWait->alias         = static::$WAITING_TITLE.$postCmsPageWait->alias;

            $ret = $this->createPage($postCmsPageWait);
        }
        return $ret;
    }

    /**
     *
     * @param PostCmsCreatePage $postCmsPage
     * @return Nav
     */
    protected function createTksPage(PostCmsCreatePage $postCmsPage)
    {
        $ret = null;
        if (!empty($this->tks_template_page_id)) {
            $postCmsPageTks                = clone $postCmsPage;
            $postCmsPageTks->use_draft     = true;
            $postCmsPageTks->from_draft_id = $this->tks_template_page_id;
            $postCmsPageTks->title         = static::$TKS_TITLE.$postCmsPageTks->title;
            $postCmsPageTks->alias         = static::$TKS_TITLE.$postCmsPageTks->alias;

            $ret = $this->createPage($postCmsPageTks);
        }
        return $ret;
    }

    protected function createPage(PostCmsCreatePage $postCmsPage): Nav
    {
        $create = "";

        $this->menuFlush();
        $model          = new Nav();
        $fromDraft      = $postCmsPage->use_draft;
        $parentNavId    = $postCmsPage->parent_nav_id;
        $navContainerId = $postCmsPage->nav_container_id;

        if (!empty($parentNavId)) {
            $navContainerId = Nav::findOne($parentNavId)->nav_container_id;
        }

        if (!empty($fromDraft)) {
            $create = $model->createPageFromDraft($parentNavId, $navContainerId, $postCmsPage->lang_id,
                $postCmsPage->title, $postCmsPage->alias, $postCmsPage->description, $postCmsPage->from_draft_id,
                $postCmsPage->is_draft);
        } else {
            $create = $model->createPage($parentNavId, $navContainerId, $postCmsPage->lang_id, $postCmsPage->title,
                $postCmsPage->alias, $postCmsPage->layout_id, $postCmsPage->description, $postCmsPage->is_draft);
        }
        return $model;
    }

    protected function menuFlush()
    {
        if (\Yii::$app->get('menu', false)) {
            \Yii::$app->menu->flushCache();
        }
    }

    /**
     *
     * @param PostCmsCreatePage $page
     * @return boolean
     */
    public function deleteCmsPage(PostCmsCreatePage $page): CmsResultCreatePage
    {
        $url_front = $this->moduleCms->frontendUrl.'/api/1/delete-page';
        return $this->pageRestClient($url_front, $page, CmsResultFormat::JSON);
    }

    /**
     *
     * @param PostCmsCreatePage $page
     * @return string
     */
    public function getPreviewUrlCmsPage(PostCmsCreatePage $page): CmsResultCreatePage
    {
        $url_front = $this->moduleCms->frontendUrl.'/api/1/preview-page';
        return $this->pageRestClient($url_front, $page, CmsResultFormat::JSON);
    }

    /**
     *
     * @param PostCmsCreatePage $page
     * @return CmsResultHtmlPage
     */
    public function getPreviewUrlCmsPageHtml(PostCmsCreatePage $page): CmsResultHtmlPage
    {
        $url_front = $this->moduleCms->frontendUrl.'/api/1/preview-page-html';
        return $this->pageRestClient($url_front, $page, CmsResultFormat::HTML);
    }

    /**
     *
     * @param PostCmsCreatePage $page
     * @return CmsResultCreatePage
     */
    public function updateCmsPage(PostCmsCreatePage $postCmsPage): CmsResultCreatePage
    {
        $result                      = new CmsResultCreatePage();
        $request                     = Yii::$app->request;
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $this->menuFlush();
        $model = new Nav();

        if (!is_null($postCmsPage)) {

            if ($postCmsPage->validate()) {
                \open20\luya\admin\module\Module::setBackendUserId($postCmsPage->cms_user_id);
                if (!empty($postCmsPage->nav_id)) {
                    $model = Nav::findOne($postCmsPage->nav_id);
                    if (!is_null($model)) {
                        $result->nav_id      = $model->id;
                        $result->preview_url = $model->getPreviewUrl();
                        $landing             = new \open20\luya\frontend\api\utility\CmsLandigBuilder([], $postCmsPage);

                        $landing->buidTks();
                        $landing->buildWaiting();
                        $landing->buildAlready();

                        $landing->build();
                    }
                }
            }
        }

        if (isset(\Yii::$app->cache)) {
            \Yii::$app->cache->flush();
        }
        if (isset(\Yii::$app->translateCache)) {
            \Yii::$app->translateCache->flush();
        }
        return $result;
    }

    /**
     *
     * @param string $url
     * @param PostCmsCreatePage $page
     * @return CmsResultCreatePage
     */
    protected function pageRestClient(string $url, PostCmsCreatePage $page, string $format): CmsResultPage
    {

        $a = microtime(true);
        $h = fopen('/var/www/stage-hosts/events-pre-prod.stage.demotestwip.it/cmsutilitylog.log', 'a+');

        try {

            $response       = $this->client->createRequest()
                ->setMethod('POST')
                ->setUrl($url)
                ->setCookies($this->loginCookies)
                ->setData([
                    'nav_id' => $page->nav_id,
                    'is_draft' => $page->is_draft,
                    'alias' => $page->alias,
                    'description' => $page->description,
                    'title' => $page->title,
                    'from_draft_id' => $page->from_draft_id,
                    'lang_id' => $page->lang_id,
                    'layout_id' => $page->layout_id,
                    'nav_container_id' => $page->nav_container_id,
                    'parent_nav_id' => $page->parent_nav_id,
                    'use_draft' => $page->use_draft,
                    'cms_user_id' => $page->cms_user_id,
                    'with_login' => $page->with_login,
                    'form_landing' => $page->form_landing->json_encode(),
                    'event_data' => $page->event_data->json_encode(),
                    ]
                )
                ->setHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->setOptions([
                    'timeout' => 600, // set timeout to 600 seconds for the case server is not responding
                ])
                ->send();
            fwrite($h, 'Riga 197 '.$url.': '.(microtime(true) - $a)."\n");
            $result         = CmsResultFormat::format($format);
            fwrite($h, 'Riga 199 '.$url.': '.(microtime(true) - $a)."\n");
            $result->build($response->content);
            fwrite($h, 'Riga 201 '.$url.': '.(microtime(true) - $a)."\n");
            $result->status = $response->isOk;
        } catch (\yii\base\Exception $e) {
            \Yii::getLogger()->log($ex->getTraceAsString(), \yii\log\Logger::LEVEL_ERROR);
            $result = new CmsResultCreatePage();
            fwrite($h, 'Riga 208 '.$url.': '.(microtime(true) - $a)."\n");
        }
        fwrite($h, 'Riga 210 FINE '.$url.': '.(microtime(true) - $a)."\n");
        fclose($h);
        return $result;
    }

    /**
     *
     * @return array
     */
    public function getCmsTemplates()
    {
        $drafts = MenuHelper::getDrafts();

        $removeIds = [];

        $landing = new CmsLandigBuilder();

        foreach ($drafts as $draft) {
            $data = $landing->getDataFromTemplate($draft['id']);
            if (!is_null($data)) {
                $removeIds[] = $data->already_present_template_page_id;
                $removeIds[] = $data->tks_template_page_id;
                $removeIds[] = $data->waiting_template_page_id;
            }
        }
        foreach ($drafts as $draft) {

            if (ArrayHelper::isIn($draft['id'], $removeIds)) {
                ArrayHelper::removeValue($drafts, $draft);
            }
        }

        return $drafts;
    }

    /**
     *
     * @return array
     */
    public function getCmsLanguages()
    {
        $languages = [];
        $request   = Yii::$app->request;

        if ($request->isAjax) {
            $languages = Lang::getQuery();
        }
        return $languages;
    }

    /**
     * @param $alias
     * @param null $nav_id
     * @return bool|mixed
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function isUrlOk($alias, $nav_id = null)
    {
        $count = NavItem::find()
                ->andWhere(['alias' => $alias])
                ->andFilterWhere(['!=', 'nav_id', $nav_id])->count();
        return $count == 0;
    }
}