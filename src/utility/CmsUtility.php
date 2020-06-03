<?php

/**
 * Aria S.p.A.
 * OPEN 2.0
 *
 *
 * @package    Open20Package
 * @category   CategoryName
 */

namespace open20\cmsbridge\utility;

use open20\cmsbridge\models\CmsResultCreatePage;
use open20\cmsbridge\models\CmsResultFormat;
use open20\cmsbridge\models\CmsResultHtmlPage;
use open20\cmsbridge\models\CmsResultPage;
use open20\cmsbridge\models\PostCmsCreatePage;
use open20\amos\mobile\bridge\modules\v1\models\User as TokenUser;
use Exception;
use Yii;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\log\Logger;

class CmsUtility
{
    protected $client;
    protected $loginCookies = null;

    public function __construct()
    {
        $this->client = new Client();
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
            $url_front = Yii::$app->params['platform']['frontendUrl'].'/admin/login/login-amos';

            $response = $this->client->createRequest()
                ->setMethod('GET')
                ->setUrl($url_front)
                ->setData(['secure_token' => $token])
                ->send();

            if ($response->isOk) {
                $values  = $this->decodeLoginData($response->content);
                $user_id = $values['user_id'];
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
     * @return boolean
     */
    public function createCmsPage(PostCmsCreatePage $page): CmsResultCreatePage
    {
        $url_front = Yii::$app->params['platform']['frontendUrl'].'/api/1/create-page';
        return $this->pageRestClient($url_front, $page, CmsResultFormat::JSON);
    }

    /**
     *
     * @param PostCmsCreatePage $page
     * @return string
     */
    public function getPreviewUrlCmsPage(PostCmsCreatePage $page): CmsResultCreatePage
    {
        $url_front = Yii::$app->params['platform']['frontendUrl'].'/api/1/preview-page';
        return $this->pageRestClient($url_front, $page, CmsResultFormat::JSON);
    }

    /**
     *
     * @param PostCmsCreatePage $page
     * @return CmsResultHtmlPage
     */
    public function getPreviewUrlCmsPageHtml(PostCmsCreatePage $page): CmsResultHtmlPage
    {
        $url_front = Yii::$app->params['platform']['frontendUrl'].'/api/1/preview-page-html';
        return $this->pageRestClient($url_front, $page, CmsResultFormat::HTML);
    }

    /**
     *
     * @param PostCmsCreatePage $page
     * @return CmsResultCreatePage
     */
    public function updateCmsPage(PostCmsCreatePage $page): CmsResultCreatePage
    {
        $url_front = Yii::$app->params['platform']['frontendUrl'].'/api/1/update-page';
        return $this->pageRestClient($url_front, $page, CmsResultFormat::JSON);
    }

    /**
     * 
     * @param string $url
     * @param PostCmsCreatePage $page
     * @return CmsResultCreatePage
     */
    protected function pageRestClient(string $url, PostCmsCreatePage $page,
                                      string $format): CmsResultPage
    {
        
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
            ->send();
        $result         = CmsResultFormat::format($format);
        $result->build($response->content);
        $result->status = $response->isOk;
        return $result;
    }

    /**
     *
     * @return array
     */
    public function getCmsTemplates()
    {
        $templates = [];

        $url_front = Yii::$app->params['platform']['frontendUrl'].'/api/1/list-templates';

        $client   = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setCookies($this->loginCookies)
            ->setUrl($url_front)
            ->setData([])
            ->setHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->send();
        if ($response->isOk) {
            $templates = $response->data;
        }

        return $templates;
    }

    /**
     *
     * @return array
     */
    public function getCmsLanguages()
    {
        $languages = [];

        $url_front = Yii::$app->params['platform']['frontendUrl'].'/api/1/list-languages';

        $client   = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($url_front)
            ->setCookies($this->loginCookies)
            ->setData([])
            ->setHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->send();
        if ($response->isOk) {
            $languages = $response->data;
        }

        return $languages;
    }
}