<?php

namespace Gutschify\Controller;

use OxidEsales\Eshop\Application\Component\Widget\WidgetController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use Gutschify\Service\GutschifyService;
use Gutschify\Exception\GutschifyException;

class GutschifyWidgetController extends WidgetController
{
    protected $_sThisTemplate = '@gutschify/gutschify_widget.html.twig';

    public const MODULE_ID = 'gutschify';

    public function render()
    {
        parent::render();

        // OXID instantiates controllers via oxNew() with no constructor args,
        // so resolve dependencies from the container here.
        $container = ContainerFactory::getInstance()->getContainer();
        $settings = $container->get(ModuleSettingServiceInterface::class);
        $gutschify = $container->get(GutschifyService::class);
        $logger = Registry::getLogger();

        // getString() returns a UnicodeString in OXID 7; cast before using.
        $baseUrl = (string) $settings->getString('gutschify_base_url', self::MODULE_ID);
        $organizationId = (string) $settings->getString('organization_id', self::MODULE_ID);
        $collectionSlug = (string) $settings->getString('collection_slug', self::MODULE_ID) ?: 'default';
        $widgetTitle = (string) $settings->getString('widget_title', self::MODULE_ID);
        $cacheEnabled = $settings->getBoolean('cache_enabled', self::MODULE_ID);
        $cacheTtl = (int) ((string) $settings->getString('cache_ttl', self::MODULE_ID) ?: '3600');

        $content = '';
        $error = '';

        if ($baseUrl === '' || $organizationId === '') {
            $error = 'Gutschify module is not properly configured. Please check module settings.';
        } else {
            try {
                $content = $gutschify->fetchEmbeddedHome(
                    $baseUrl,
                    $organizationId,
                    $collectionSlug,
                    $cacheEnabled,
                    $cacheTtl
                );
            } catch (GutschifyException $e) {
                $error = 'Failed to load Gutschify content: ' . $e->getMessage();
                $logger->error('Gutschify error: ' . $e->getMessage());
            } catch (\Exception $e) {
                $error = 'An unexpected error occurred.';
                $logger->error('Gutschify unexpected error: ' . $e->getMessage());
            }
        }

        $this->addTplParam('gutschify_content', $content);
        $this->addTplParam('gutschify_error', $error);
        $this->addTplParam('gutschify_widget_title', $widgetTitle);

        return $this->_sThisTemplate;
    }
}
