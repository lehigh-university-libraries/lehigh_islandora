<?php

declare(strict_types=1);

namespace Drupal\lehigh_islandora\EventSubscriber;

use Drupal\Core\File\FileSystemInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Cache node canonical on disk.
 */
final class CacheNodeCanonical implements EventSubscriberInterface {

  /**
   * Listen for node canonical requests.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function getCachedNodeView(RequestEvent $event) : void {
    // See if we're dealing with a book manifest request.
    $request = $event->getRequest();
    if (!$this->applies($request, TRUE)) {
      return;
    }

    // Now we know we're dealing with a IIIF manifest route, so
    // see if we have a cached response on disk.
    $path = $request->getPathInfo();
    $file_path = self::getCachedFilePath($request, $path);

    if (file_exists($file_path)) {
      $file_contents = file_get_contents($file_path);
      $response = new Response($file_contents, Response::HTTP_OK);
      $response->headers->set('Cache-Control', 'max-age=3600, public');
      $response->headers->set('Content-Type', 'text/html');
      $response->headers->set('X-Drupal-Cache', 'HIT');
      $age = (string) (time() - filemtime($file_path));
      $response->headers->set('X-Age', $age);
      $event->setResponse($response);
    }
  }

  /**
   * Listen for node canonical responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function setCachedNodeView(ResponseEvent $event) : void {
    // See if the response is for a book manifest.
    $request = $event->getRequest();
    if (!$this->applies($request)) {
      return;
    }

    $response = $event->getResponse();
    if ($response->getStatusCode() !== 200) {
      return;
    }

    $path = $request->getPathInfo();
    $file_path = self::getCachedFilePath($request, $path);
    $invalidating = $request->query->get('cache-warmer', FALSE);
    // If we're invalidating the cache
    // OR the cached file doesn't exist create it from the response.
    if ($invalidating || !file_exists($file_path)) {
      $body = $response->getContent();
      $dir = dirname($file_path);
      $filesystem = \Drupal::service('file_system');
      if ($filesystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY)) {
        // If the cache is being invalidating on this node
        // remove all its cached responses.
        if ($invalidating) {
          $node = FALSE;
          if ($request->attributes->has('node')) {
            $nid = $request->attributes->get('node');
            $node = is_object($nid) ? $nid : Node::load($nid);
          }

          // If we're invalidating a node, clear its disk cache.
          if ($node) {
            self::clearDiskCache($node);
          }
          // If this is not a node response that's cached
          // base it on the path.
          else {
            $base_dir = $filesystem->realpath('private://canonical');
            if ($base_dir) {
              $pattern = "$base_dir/*/$path/*.html";
              array_map('unlink', glob($pattern));
            }
          }
        }

        $f = fopen($file_path, 'w');
        if ($f) {
          fwrite($f, $body);
          fclose($f);
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
        ['getCachedNodeView'],
      ],
      KernelEvents::RESPONSE => [
        ['setCachedNodeView'],
      ],
    ];
  }

  /**
   *
   */
  protected function applies(Request $request, $get = FALSE): bool {
    // Bail if we want to force a regeneration and we're fetching cache.
    if ($get && $request->query->get('cache-warmer', FALSE)) {
      return FALSE;
    }

    // Only apply on the node canonical view or our collections/browse views.
    $route_name = $request->attributes->get('_route');
    if (in_array($route_name, ["view.browse.main", "entity.node.canonical"])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Helper function to get the path to cache the node canonical HTML to.
   */
  protected static function getCachedFilePath(Request $request, string $path): string {
    $filesystem = \Drupal::service('file_system');
    $base_dir = 'private://canonical';

    // Make a subdirectory based on the current user ID.
    $base_dir .= '/' . \Drupal::currentUser()->id();

    // Another dir based on the current path.
    $base_dir .= '/' . $path;
    $filesystem->prepareDirectory($base_dir, FileSystemInterface::CREATE_DIRECTORY);
    $base_dir = $filesystem->realpath($base_dir);

    $file_path = $base_dir . '/';

    // Make the filename based on any URL parameters.
    $queryParams = $request->query->all();
    // But don't include our special cache param to reset index.
    unset($queryParams['cache-warmer']);
    if (count($queryParams)) {
      $queryParams = json_encode($queryParams);
      $file_path .= md5($queryParams) . '.html';
    }
    else {
      $file_path .= 'index.html';
    }

    return $file_path;
  }

  /**
   *
   */
  public static function clearDiskCache($node) {
    // Remove cached node canonical pages from disk
    // for this node and its parent.
    $filesystem = \Drupal::service('file_system');
    $base_dir = $filesystem->realpath('private://canonical');
    $base_dir .= '/*';

    // Wipe the cache for the node and its parent(s)
    $nids = [$node->id()];
    if ($node->hasField('field_member_of')) {
      foreach ($node->field_member_of as $parent) {
        if (is_null($parent->entity)) {
          continue;
        }
        $nids[] = $parent->entity->id();
      }
    }

    // Perform the cache clear.
    foreach ($nids as $nid) {
      $pattern = $base_dir . '/node/' . $nid . '/*.html';
      array_map('unlink', glob($pattern));

      // Wipe any aliases for these nodes.
      foreach (['node', 'browse-items'] as $arg0) {
        $alias = \Drupal::service('path_alias.manager')->getAliasByPath("/$arg0/$nid");
        $pattern = $base_dir . '/' . $alias . '/*.html';
        array_map('unlink', glob($pattern));
      }
    }

    // Also clear our views.
    foreach (['browse', 'collections'] as $dir) {
      $pattern = "$base_dir/$dir/*.html";
      array_map('unlink', glob($pattern));
    }
  }

}
