<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Service\ItemsService;
use TinyApp\Model\Validator\ValidatorFactory;
use TinyApp\Model\Validator\ItemsAddValidator;
use TinyApp\Model\Validator\ItemEditValidator;

class SampleController implements ControllerInterface
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

        return new Response(
            'list.php',
            ['items' => $items]
        );
    }

    public function details(Request $request) : Response
    {
        list($id) = array_values($request->getAttributes(['id']));
        $item = $this->itemsService->getItem($id);
        if (empty($item)) {
            return new Response(null, [], [], ['Location' => '/items']);
        }

        return new Response(
            'item.php',
            ['item' => $item]
        );
    }

    public function add(Request $request) : Response
    {
        $validator = $this->validatorFactory->create(ItemsAddValidator::class);
        if ($request->getMethod() === 'POST') {
            $payload = $request->getPayload(['items']);
            if ($validator->check($payload)) {
                $ids = $this->itemsService->saveItems($payload['items']);
                if (empty($ids)) {
                    return new Response(
                        'addForm.php',
                        ['error' => 'Could not add items']
                    );
                }

                return new Response(null, [], [], ['Location' => $request->getHost() . '/app.php/items']);
            }
        }

        return new Response(
            'addForm.php',
            ['error' => $validator->getError()]
        );
    }

    public function edit(Request $request) : Response
    {
        list($id) = array_values($request->getAttributes(['id']));
        $item = $this->itemsService->getItem($id);
        if (empty($item)) {
            return new Response(null, [], [], ['Location' => '/items']);
        }

        $validator = $this->validatorFactory->create(ItemEditValidator::class);
        if ($request->getMethod() === 'POST') {
            $payload = $request->getPayload(['name']);
            if ($validator->check($payload)) {
                $payload['id'] = $id;
                $updatedId = $this->itemsService->updateItem($payload);
                if (empty($updatedId)) {
                     return new Response(
                        'addForm.php',
                        ['error' => 'Could not update item']
                    );
                }

                return new Response(null, [], [], ['Location' => $request->getHost() . '/app.php/items/' . $id]);
            }
        }

        return new Response(
            'editForm.php',
            ['error' => $validator->getError(), 'item' => $item]
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
