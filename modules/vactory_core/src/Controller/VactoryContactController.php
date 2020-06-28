<?php
//
//namespace Drupal\vactory_core\Controller;
//
//use Drupal\contact\ContactFormInterface;
//use Drupal\contact\Controller\ContactController;
//use Drupal\Core\Url;
//use Symfony\Component\HttpFoundation\RedirectResponse;
//
///**
// * Class VactoryContactController.
// * Alter default drupal contact form.
// *
// * @package Drupal\vactory_core\Controller
// */
//class VactoryContactController extends ContactController {
//
//    /**
//     * Presents the site-wide contact form.
//     *
//     * @param \Drupal\contact\ContactFormInterface $contact_form
//     *   The contact form to use.
//     *
//     * @return array
//     *   The form as render array as expected by
//     *   \Drupal\Core\Render\RendererInterface::render().
//     *
//     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
//     *   Exception is thrown when user tries to access non existing default
//     *   contact form.
//     */
//    public function contactSitePage(ContactFormInterface $contact_form = NULL) {
//        $url = Url::fromRoute( '<front>', [], []);
//        $this->redirectUser($url->toString());
//        return array();
//    }
//
//
//
//    /**
//     * Function redirect user to a specific URL.
//     * @param $path
//     */
//    public function redirectUser($path)
//    {
//        $response = new RedirectResponse($path);
//        $response->send();
//        return;
//    }
//}
