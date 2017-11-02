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
        $items = $this->itemsService->getItems();

        return new Response('home.php');
    }

    public function list(Request $request) : Response
    {
        $items = $this->itemsService->getItems();

        $validator = $this->validatorFactory->create(ItemsDeleteValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                extract($request->getPayload(['ids']));
                if (!empty($ids)) {
                    $this->itemsService->deleteItems($ids);
                    return new Response(null, [], [], ['Location' => '/items']);
                }
            }
        }

        $rules = [];
        foreach ($items as $key => $item) {
            $rules['items.' . $key . '.name'] = 'html';
        }
        $rules['error'] = 'html';

        return new Response(
            'items/list.php',
            ['items' => $items, 'error' => isset($error) ? $error : $validator->getError(), 'csrfToken' => $validator->getCsrfToken()],
            $rules
        );
    }

    public function details(Request $request) : Response
    {
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
        extract($request->getAttributes(['id']));
        $item = $this->itemsService->getItem($id);
        if (empty($item)) {
            return new Response(null, [], [], ['Location' => '/items']);
        }

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

    public function restricted(Request $request)
    {
        return new Response(
            'restricted.php',
            ['restrictedValue' => '<p style="color:red">This is restrictd site '. $request->getAttributes(['code'])['code'] .'</p>'],
            ['restrictedValue' => 'raw']
        );
    }
}
