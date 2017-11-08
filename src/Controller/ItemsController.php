<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Service\ItemsService;
use TinyApp\Model\Validator\ValidatorFactory;
use TinyApp\Model\Validator\ItemsAddValidator;
use TinyApp\Model\Validator\ItemsDeleteValidator;
use TinyApp\Model\Validator\ItemEditValidator;

class ItemsController implements ControllerInterface
{
    private $itemsService;
    private $validatorFactory;

    public function __construct(ItemsService $itemsService, ValidatorFactory $validatorFactory)
    {
        $this->itemsService = $itemsService;
        $this->validatorFactory = $validatorFactory;
    }

    public function home(Request $request) : Response
    {
        return new Response('home.php');
    }

    public function list(Request $request) : Response
    {
        // Get items for page and redirect to first page if empty
        extract($request->getAttributes(['page']));
        $page = $page ?? 1;
        $itemsPack = $this->itemsService->getItems($page);
        if (empty($itemsPack['items'])) {
            return new Response(null, [], [], ['Location' => '/items']);
        }

        // Delete selected items and redirect to current page
        $validator = $this->validatorFactory->create(ItemsDeleteValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                extract($request->getPayload(['ids']));
                if (!empty($ids)) {
                    $this->itemsService->deleteItems($ids);
                    return new Response(null, [], [], ['Location' => '/items/list/' . $page]);
                }
            }
        }

        // Set html escape rule for items names and error
        $rules = [];
        foreach ($itemsPack['items'] as $key => $item) {
            $rules['items.' . $key . '.name'] = 'html';
        }
        $rules['error'] = 'html';

        return new Response(
            'items/list.php',
            [
                'items' => $itemsPack['items'],
                'page' => $itemsPack['page'],
                'pages' => $itemsPack['pages'],
                'error' => isset($error) ? $error : $validator->getError(),
                'csrfToken' => $validator->getCsrfToken()
            ],
            $rules
        );
    }

    public function details(Request $request) : Response
    {
        // Get item details and redirect to items list if empty
        extract($request->getAttributes(['id']));
        $item = $this->itemsService->getItem($id);
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
                $payload = $request->getPayload(['items']);
                $ids = $this->itemsService->saveItems($payload['items']);
                if (!empty($ids)) {
                    return new Response(null, [], [], ['Location' => $request->getHost() . '/app.php/items']);
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
        extract($request->getAttributes(['id']));
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
                    return new Response(null, [], [], ['Location' => $request->getHost() . '/app.php/items/' . $id]);
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
