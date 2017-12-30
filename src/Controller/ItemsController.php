<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Service\ItemsService;
use TinyApp\Model\Service\SessionService;
use TinyApp\Model\Validator\ValidatorFactory;
use TinyApp\Model\Validator\ItemsAddValidator;
use TinyApp\Model\Validator\ItemsDeleteValidator;
use TinyApp\Model\Validator\ItemEditValidator;

class ItemsController implements ControllerInterface
{
    private $itemsService;
    private $validatorFactory;
    private $sessionService;

    public function __construct(ItemsService $itemsService, ValidatorFactory $validatorFactory, SessionService $sessionService)
    {
        $this->itemsService = $itemsService;
        $this->validatorFactory = $validatorFactory;
        $this->sessionService = $sessionService;
    }

    public function home(Request $request) : Response
    {
        return new Response('home.php');
    }

    public function list(Request $request) : Response
    {
        // Get items for page and redirect to first page if empty
        $page = $request->getAttributes(['page'])['page'] ?? 1;

        // Delete selected items and redirect to current page
        $validator = $this->validatorFactory->create(ItemsDeleteValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                $ids = $request->getPayload(['ids'])['ids'];
                if (!empty($ids)) {
                    if ($this->itemsService->deleteItems($ids)) {
                        $this->sessionService->set(['flash' => ['type' => 'success', 'text' => 'Items are deleted']]);
                    } else {
                        $this->sessionService->set(['flash' => ['type' => 'fail', 'text' => 'Items are not deleted']]);
                    }

                    return new Response(null, [], [], ['Location' => '/items/list/' . $page]);
                }
            }
        }

        // Get items
        $itemsPack = $this->itemsService->getItems($page);
        if (empty($itemsPack['items']) && $page !== 1) {
            return new Response(null, [], [], ['Location' => '/items']);
        }

        // Set html escape rule for items names and error
        $rules = ['error' => 'html', 'flash' => 'html'];
        foreach ($itemsPack['items'] as $key => $item) {
            $rules['items.' . $key . '.name'] = 'html';
        }

        return new Response(
            'items/list.php',
            [
                'items' => $itemsPack['items'],
                'page' => $itemsPack['page'],
                'pages' => $itemsPack['pages'],
                'flash' => $this->sessionService->get(['flash'], true)['flash'],
                'error' => isset($error) ? $error : $validator->getError(),
                'csrfToken' => $validator->getCsrfToken()
            ],
            $rules
        );
    }

    public function details(Request $request) : Response
    {
        // Get item details and redirect to items list if empty
        $item = $this->itemsService->getItem($request->getAttributes(['id'])['id']);
        if (empty($item)) {
            return new Response(null, [], [], ['Location' => '/items']);
        }

        return new Response(
            'items/details.php',
            ['item' => $item],
            ['item' => 'html']
        );
    }

    public function add(Request $request) : Response
    {
        // Create items and redirect items list
        $validator = $this->validatorFactory->create(ItemsAddValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                $ids = $this->itemsService->saveItems($request->getPayload(['items'])['items']);
                if (!empty($ids)) {
                    $this->sessionService->set(['flash' => ['type' => 'success', 'text' => 'Items are added']]);
                    return new Response(null, [], [], ['Location' => '/items']);
                }
                $error = 'Failed to update item';
            }
        }

        return new Response(
            'items/addForm.php',
            ['error' => isset($error) ? $error : $validator->getError(), 'csrfToken' => $validator->getCsrfToken()],
            ['error' => 'html']
        );
    }

    public function edit(Request $request) : Response
    {
        // Get item and redirect to items list if empty
        $id = $request->getAttributes(['id'])['id'];
        $item = $this->itemsService->getItem($id);
        if (empty($item)) {
            return new Response(null, [], [], ['Location' => '/items']);
        }

        // Edit item and redirect to item details page
        $validator = $this->validatorFactory->create(ItemEditValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                $payload = $request->getPayload(['name']);
                $payload['id'] = $id;
                $updatedId = $this->itemsService->updateItem($payload);
                if (!empty($updatedId)) {
                    return new Response(null, [], [], ['Location' => '/items/' . $id]);
                }
                $error = 'Failed to update item';
            }
        }

        return new Response(
            'items/editForm.php',
            ['error' => isset($error) ? $error : $validator->getError(), 'item' => $item, 'csrfToken' => $validator->getCsrfToken()],
            ['error' => 'html']
        );
    }
}
