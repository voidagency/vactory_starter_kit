<?php

namespace Drupal\vactory_mailchimp_newsletter\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Mailchimp\MailchimpCampaigns;

/**
 * Provides methods related to campaign creation and scheduling.
 */
class CampaignsService {

  /**
   * Logger Channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * Entity Type Manager Interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new AnnouncementsService.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager Interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, RendererInterface $renderer) {
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->renderer = $renderer;
  }

  /**
   * Returns all MailChimp lists for a given key.
   *
   * @return array
   *   An array of lists.
   */
  public function mailchimpGetInfos() {
    $infos = [];
    $config_data = $this->configFactory->getEditable('webform.webform.' . 'newsletter')->getRawData();

    try {
      if (!empty($config_data)) {
        $handler_setting = isset($config_data['handlers']['vactory_mailchimp_webform_handler']['settings']) ? $config_data['handlers']['vactory_mailchimp_webform_handler']['settings'] : '';
        if (!empty($handler_setting)) {
          $infos['api_key'] = isset($handler_setting['api_key']) ? $handler_setting['api_key'] : '';
          $infos['server_prefix'] = isset($handler_setting['server_prefix']) ? $handler_setting['server_prefix'] : '';
          $infos['list_id'] = isset($handler_setting['list_id']) ? $handler_setting['list_id'] : '';
        }
      }
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->get('newsletter_mailchimp')->error('An error occurred requesting Mailchimp information from Vactory mailchimp webform handler. "{message}"', ['message' => $e->getMessage()]);
    }
    return $infos;
  }

  /**
   * Create a campaign in MailChimp.
   *
   * @param string $template
   *   Template content for campaign.
   * @param object $recipients
   *   List settings for the campaign.
   * @param object $settings
   *   Campaign settings.
   *
   * @return string
   *   New campaign ID.
   */
  public function mailchimpCreateCampaign($template, $recipients, $settings) {
    $content_parameters = [
      'html' => $template,
    ];
    /** @var \Mailchimp\MailchimpCampaigns $mc_campaigns */
    $mc_campaigns = mailchimp_get_api_object('MailchimpCampaigns');
    try {
      if (!$mc_campaigns) {
        throw new \Exception('Cannot create campaign without Mailchimp API. Check API key has been entered.');
      }
      $result = $mc_campaigns->addCampaign(MailchimpCampaigns::CAMPAIGN_TYPE_REGULAR, $recipients, $settings);

      if (!empty($result->id)) {
        $campaign_id = $result->id;
        $this->loggerChannelFactory->get('newsletter_mailchimp')->notice(t('Campaign %name (%cid) was successfully saved on Mailchimp.',
          ['%name' => $settings->title, '%cid' => $campaign_id]));
        $mc_campaigns->setCampaignContent($campaign_id, $content_parameters);
      }
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->get('newsletter_mailchimp')->error('An error occurred while creating this campaign on mailchimp: {message}', [
        'message' => $e->getMessage(),
      ]);
      return NULL;
    }
    return $campaign_id;
  }

  /**
   * Sends a Mailchimp campaign.
   *
   * @return bool
   *   TRUE if campaign is sent successfully.
   */
  public function mailchimpSendCampaign($campaign) {
    /** @var \Mailchimp\MailchimpCampaigns $mc_campaign */
    $mc_campaign = mailchimp_get_api_object('MailchimpCampaigns');
    // Send campaign.
    try {
      $mc_campaign->send($campaign->id);
      $result = $mc_campaign->getCampaign($campaign->id);
      if (($result->status == MAILCHIMP_STATUS_SENDING) || ($result->status == MAILCHIMP_STATUS_SENT)) {
        // Log action, and notify the user.
        $this->loggerChannelFactory->get('newsletter_mailchimp')->notice('Mailchimp campaign {id} has been sent.', [
          'id' => $campaign->id,
        ]);

        $controller = $this->entityTypeManager->getStorage('mailchimp_campaign');
        $controller->resetCache([$campaign->id]);
        $cache = \Drupal::cache('mailchimp');
        $cache->invalidate('campaigns');
        $cache->invalidate('campaign_' . $campaign->id);

        return TRUE;
      }
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
      $this->loggerChannelFactory->get('newsletter_mailchimp')
        ->error('An error occurred while sending to this campaign: {message}', [
          'message' => $e->getMessage(),
        ]);
    }
    return FALSE;
  }

  /**
   * Sends a Newsletter.
   */
  public function mailchimpSendNewsletter($node, $list_id) {
    $mc_campaign = mailchimp_get_api_object('MailchimpCampaigns');
    $url = Url::fromRoute('view.newsletter.listing');
    try {
      if (!$mc_campaign) {
        throw new \Exception('Cannot send campaign without Mailchimp API. Check API key has been entered.');
      }

      if (!empty($list_id)) {
        $recipients = (object) [
          'list_id' => $list_id,
        ];

        $site_infos = \Drupal::config('system.site');
        $user_mail = $site_infos->get('mail');
        $user_display_name = $site_infos->get('name');

        $settings = (object) [
          'subject_line' => $node->get('field_vactory_title')->value,
          'title' => $node->getTitle(),
          'from_name' => $user_display_name,
          'reply_to' => $user_mail,
        ];

        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $render_controller = $this->entityTypeManager->getViewBuilder($node->getEntityTypeId());
        // Set view mode.
        $view_mode = 'mailchimp_newsletter';
        $render_output = $render_controller->view($node, $view_mode, $langcode);
        // Render the template.
        $renderer = $this->renderer;
        $template = $this->renderer->executeInRenderContext(new RenderContext(), static function () use ($render_output, $renderer) {
          return $renderer->render($render_output);
        });
        // Create the campaign in mailchimp.
        $campaign_id = $this->mailchimpCreateCampaign($template, $recipients, $settings);
        $campaign = $mc_campaign->getCampaign($campaign_id);
        // Send created campaign via mailchimp.
        $this->mailchimpSendCampaign($campaign);
        \Drupal::messenger()->addStatus(t('Newsletter %name (%cid) was successfully sent via Mailchimp.',
          ['%name' => $settings->title, '%cid' => $campaign_id]));
      }
    }
    catch (\Exception $e) {
      $response['message'] = t("An exception occurred while sending the Newsletter : @message", ['@message' => $e]);
      $this->loggerChannelFactory->get('newsletter_mailchimp')->error($response['message']);
      \Drupal::messenger()->addError($response['message']);
    }
  }

}
