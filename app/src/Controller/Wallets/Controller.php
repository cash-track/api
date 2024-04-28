<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Controller\AuthAwareController;
use Spiral\Http\Request\InputManager;

class Controller extends AuthAwareController
{
    /**
     * @param \Spiral\Http\Request\InputManager $input
     * @param string $key
     * @return int[]
     */
    protected function fetchFilteredTagIDs(InputManager $input, string $key = 'tags'): array
    {
        $ids = $input->query->get($key, '');
        $ids = explode(',', $ids);
        $ids = array_map(fn($id) => (int) $id, $ids);
        return array_filter($ids, fn($id) => $id > 0);
    }
}
