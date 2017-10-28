<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Service\ItemsService;
use TinyApp\Model\Validator\ValidatorFactory;
use TinyApp\Model\Validator\ItemsAddValidator;
use TinyApp\Model\Validator\ItemEditValidator;
use TinyApp\Model\Service\SessionService;

//@TODO it is for tests only
use TinyApp\Model\Repository\FileManager;

class SampleController implements ControllerInterface
{
    private $itemsService;
    private $validatorFactory;

    public function __construct(ItemsService $itemsService, ValidatorFactory $validatorFactory, FileManager $fm)
    {
        $this->itemsService = $itemsService;
        $this->validatorFactory = $validatorFactory;
        $this->fm = $fm;
    }

    public function test(Request $request) : Response
    {
        

        if ($request->getMethod() === 'POST') {
            var_dump(1);exit;
        }
        return new Response(
            'test.php',
            ['what' => 'ergg', 'blahKey' => 'blah value', 'another' => ['key eh', 'text' => '<p>test</p>']],
            ['another.text' => 'html']
        );
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
            'items/list.php',
            ['items' => $items],
            ['items' => 'html']
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
