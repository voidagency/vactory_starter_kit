<?php

namespace Drupal\vactory_decoupled_router\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a listing of Route.
 */
class RouteListBuilder extends ConfigEntityListBuilder
{
    private $iconCheck = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:30px; height:30px; color: #1bda0b"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
    private $iconWarn = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:30px; height:30px; color: #ff5013"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>';
    
    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['id'] = $this->t('Machine name');
        $header['label'] = $this->t('Route');
        $header['path'] = $this->t('Path');
        $header['alias'] = $this->t('Alias');
        $header['status'] = $this->t('Status');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        $router = \Drupal::service('router.no_access_checks');
        $route_ok = TRUE;
        $warn_message = t("Route seems OK");
        try {
            $match_info = $router->match($entity->getAlias());
            if ($match_info['_route'] !== 'entity.node.canonical') {
                $route_ok = FALSE;
                $warn_message = t("Route should be entity.node.canonical and not :route, visit the page and inspect.", [":route" => $match_info['_route']]);
            }
        } catch (\Exception $e) {
        }
        $status_icon = $route_ok ? $this->iconCheck : $this->iconWarn;
        
        $row['id'] = $entity->id();
        $row['label'] = $entity->label();
        $row['path'] = $entity->getPath();
        $row['alias'] = $entity->getAlias();
        $row['status'] = new FormattableMarkup('<div style="text-algin:center" title="'.$warn_message.'">' . $status_icon . '</div>', []);

        return $row + parent::buildRow($entity);
    }
}
