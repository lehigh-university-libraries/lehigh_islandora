<?php

declare(strict_types=1);

namespace Drupal\lehigh_islandora\EventSubscriber;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Cache book manifests on disk.
 */
final class CacheBookManifests implements EventSubscriberInterface {

  /**
   * Listen for book manifest requests.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function getCachedManifest(RequestEvent $event) : void {
    // see if we're dealing with a book manifest request.
    $request = $event->getRequest();
    $path = $request->getPathInfo();
    $regex = '/^\/node\/\d+\/book-manifest$/';
    if (preg_match($regex, $path)) {
      // if we are, see if we have a cached response on disk.
      $file_path = self::getCachedFilePath($path);
      if (file_exists($file_path)) {
        $file_contents = file_get_contents($file_path);
        $response = new Response($file_contents, Response::HTTP_OK);            
        $response->headers->set('Cache-Control', 'max-age=86400, public');
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('X-Drupal-Cache', 'HIT');
        $event->setResponse($response);
      }
    }
  }

  /**
   * Listen for book manifest responses.
   * 
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function setCachedManifest(ResponseEvent $event) : void{
    // see if the response is for a book manifest
    $request = $event->getRequest();
    $route_name = $request->attributes->get('_route');
    if ($route_name === "view.iiif_manifest.rest_export_1") {
      $path = $request->getPathInfo();
      $file_path = self::getCachedFilePath($path);

      // if the cached file doesn't exist create it from the response.
      if (!file_exists($file_path)) {
        $body = $event->getResponse()->getContent();
        $dir = dirname($file_path);
        $filesystem = \Drupal::service('file_system');
        if ($filesystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY)) {
          $f = fopen($file_path, 'w');
          if ($f) {
            fwrite($f, $body);
            fclose($f);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => [
        ['getCachedManifest'],
      ],
      KernelEvents::RESPONSE => [
        ['setCachedManifest'],
      ],
    ];
  }

  /**
   * Helper function to get the path to cache the book manifests to.
   */
  protected static function getCachedFilePath(string $path): string {
    $filesystem = \Drupal::service('file_system');
    $base_dir = 'public://iiif';
    $base_dir = $filesystem->realpath($base_dir);
    $file_path = $base_dir . $path . '.json';

    return $file_path;
  }
}
