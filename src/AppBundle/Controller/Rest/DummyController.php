<?php

namespace AppBundle\Controller\Rest;

use FOS\RestBundle\Controller\FOSRestController;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DummyController extends FOSRestController {

	public function getDocumentManager() {
		return $this->get('doctrine_mongodb.odm.document_manager');
	}

	/**
	 * @return JsonResponse
	 */

	public function getTagsAction() {

		$tags = $this->getDocumentManager()
			->getRepository('AppBundle:Product')
			->getAllTags();
		array_unshift($tags, 'All Tags');

		$result = $this->getJsonPayload($tags);

		return new Response($result, Response::HTTP_OK);
	}

	/**
	 * @return JsonResponse
	 */

	public function getProductsAction() {

		$products = $this->getDocumentManager()
			->getRepository('AppBundle:Product')
			->findBy([], [], 50)
		;

		return new Response($this->getJsonPayload($products, 'product.all'), Response::HTTP_OK);

	}

	/**
	 * @return JsonResponse
	 */

	public function getProductAction($id) {

		$product = $this->getJsonPayload(
			$this->getDocumentManager()
				->getRepository('AppBundle:Product')
				->find($id),
			'product.single'
		);

		return new Response($product, Response::HTTP_OK);

	}

	public function getPersistanceDeserializationBatchImportAction($persistanceStrategy, $deserializationStrategy, $batchSize) {

		$importerCommand = $this->get('command.importer.products');

		$input = new ArrayInput(['--batch-size' => intval($batchSize), '--import-strategy' => $persistanceStrategy, '--deserialization-strategy' => $deserializationStrategy]);
		$output = new NullOutput();
		$importerCommand->run($input, $output);

		return new JsonResponse($importerCommand->getResult());
	}

	/**
	 *
	 * @return SerializerInterface
	 */
	protected function getSerializer() {

		static $result = null;

		if (null == $result) {
			$result = $this->get('jms_serializer');
		}
		return $result;
	}

	protected function getJsonPayload($payload, $serializationGroup = null) {

		return $serializationGroup ?

		$this->getSerializer()->serialize($payload, 'json', $this->getSerializationContext($serializationGroup)) :
		$this->getSerializer()->serialize($payload, 'json')
		;
	}

	/**
	 *
	 * @param string $group
	 * @return SerializationContext
	 */
	protected function getSerializationContext($group) {

		$context = SerializationContext::create()->setGroups(array($group))->setSerializeNull(true);

		return $context;
	}

}
