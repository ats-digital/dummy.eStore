<?php

namespace AppBundle\Annotation\Driver;

use AppBundle\Annotation\Profileable;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Stopwatch\Stopwatch;

class AnnotationDriver {

	private $reader;
	private $stopwatch;

	public function __construct($reader, $stopwatchProvider) {
		$this->reader = $reader;

		$this->stopwatch = $stopwatchProvider->getStopwatcher();
	}

	public function onKernelController(FilterControllerEvent $event) {

		if (!is_array($controller = $event->getController())) {
			return;
		}

		$object = new \ReflectionObject($controller[0]);
		$method = $object->getMethod($controller[1]);

		foreach ($this->reader->getMethodAnnotations($method) as $configuration) {

			if ($configuration instanceof Profileable) {

				$profileId = $configuration->getProfileId();

				$event->getRequest()->attributes->set('X-PROFILER-ID', $profileId);

				$this->stopwatch->start($profileId);
			}
		}
	}

	public function onKernelResponse(FilterResponseEvent $event) {

		$profileId = $event->getRequest()->attributes->get('X-PROFILER-ID');

		if ($profileId !== null) {
			$profileEvent = $this->stopwatch->stop($profileId);
			$event->getResponse()->headers->set('X-PROFILER-DURATION-MILLISECONDS', $profileEvent->getDuration());
		}

	}

}