<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Service\ItemsService;
use TinyApp\Model\Validator\ValidatorFactory;
use TinyApp\Model\Validator\ItemsAddValidator;
use TinyApp\Model\Validator\ItemEditValidator;

class ApiController implements ControllerInterface
{
    private $itemsService;
    private $validatorFactory;

    public function __construct(ItemsService $itemsService, ValidatorFactory $validatorFactory)
    {
        $this->itemsService = $itemsService;
        $this->validatorFactory = $validatorFactory;
    }

    public function cget(Request $request) : Response
    {
        // Get items for page
        extract($request->getQuery(['page']));
        if (empty($page) || $page < 1) {
            return $this->errorResponse('Query parameter page 1 or higher is required');
        }
        $itemsPack = $this->itemsService->getItems($page);

        return new Response(
            null,
            [
                'items' => $itemsPack['items'],
                'page' => $itemsPack['page'],
                'pages' => $itemsPack['pages']
            ],
            ['items' => 'html'],
            ['Content-Type' => 'application/json']
        );
    }

    public function get(Request $request) : Response
    {
        // Get selected item
        list($id) = array_values($request->getAttributes(['id']));
        $item = $this->itemsService->getItem($id);
        if (empty($item)) {
            return $this->errorResponse('No item found for id ' . $id);
        }

        return new Response(
            null,
            ['item' => $item],
            ['item' => 'html'],
            ['Content-Type' => 'application/json']
        );
    }

    public function post(Request $request) : Response
    {
        // Create item
        $validator = $this->validatorFactory->create(ItemEditValidator::class);
        if ($validator->check($request, false, false)) {
            $payload = $request->getPayload(['name']);
            $insertedId = $this->itemsService->saveItem($payload);
            if (empty($insertedId)) {
                return $this->errorResponse('Nothing inserted');
            }

            return $this->successResponse();
        }

        return $this->errorResponse($validator->getError());
    }

    public function put(Request $request) : Response
    {
        // Modify item
        list($id) = array_values($request->getAttributes(['id']));
        $validator = $this->validatorFactory->create(ItemEditValidator::class);
        if ($validator->check($request, false, false)) {
            $payload = $request->getInput(['name']);
            $payload['id'] = $id;
            $updatedId = $this->itemsService->updateItem($payload);
            if (empty($updatedId)) {
                return $this->errorResponse('Nothing updated');
            }

            return $this->successResponse();
        }

        return $this->errorResponse($validator->getError());
    }

    public function delete(Request $request) : Response
    {
        // Delete item
        list($id) = array_values($request->getAttributes(['id']));
        $deletedId = $this->itemsService->deleteItem($id);
        if (empty($deletedId)) {
            return $this->errorResponse('Nothing deleted');
        }

        return $this->successResponse();
    }

    private function errorResponse(string $error) : Response
    {
        return new Response(
            null,
            ['error' => $error],
            ['error' => 'html'],
            ['Content-Type' => 'application/json']
        );
    }

    private function successResponse() : Response
    {
        return new Response(
            null,
            ['status' => 'success'],
            [],
            ['Content-Type' => 'application/json']
        );
    }
}
