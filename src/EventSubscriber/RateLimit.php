<?php

namespace Drupal\lehigh_islandora\EventSubscriber;

use Drupal\Core\Flood\FloodInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class RateLimit.
 */
class RateLimit implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * Constructs a new RateLimit object.
   */
  public function __construct(FloodInterface $flood) {
    $this->flood = $flood;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['rateLimit'];

    return $events;
  }

  /**
   * Helper function to see if the given response needs handled by this rate limiter
   */
  protected function applies(Request $request): bool {
    // exclude on campus or local networking from rate limits
    $ip = $request->getClientIp();
    $prefix = substr($ip, 0, 7);
    if (in_array($prefix, ['128.180', '172.18.'])) {
      return FALSE;
    }

    $route_name = $request->attributes->get('_route');

    return in_array($route_name, ["view.browse.main", "view.advanced_search.page_1", "entity.node.canonical"]);
  }

  /**
   * Send a 429 if too many requests coming from IP.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function rateLimit(RequestEvent $event) {
    $request = $event->getRequest();
    if (!$this->applies($request)) {
       return;
    }

    $event_name = 'rate_limit_ip';
    $threshold = 100;
    $window = 600;
    $allowed = $this->flood->isAllowed(
      $event_name,
      $threshold,
      $window,
    );
    $this->flood->register($event_name, $window);
    if (!$allowed) {
      $response = new Response('Too many requests', 429);
      $event->setResponse($response);
      return FALSE;
    }
  }

}
