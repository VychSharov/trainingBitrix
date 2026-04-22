<?php
declare(strict_types=1);

use Bitrix\Main\Context;

/**
 * Class AbstractPage
 *
 * Base abstract page (D7).
 */
abstract class AbstractPage
{
    /**
     * Page entry point.
     *
     * @return void
     */
    final public function run(): void
    {
        $request = Context::getCurrent()->getRequest();

        if ($request->isPost()) {
            $this->handlePost();
        }

        $this->render();
    }

    /**
     * Handle POST request.
     *
     * @return void
     */
    protected function handlePost(): void
    {
        // not used yet
    }

    /**
     * Render page content.
     *
     * @return void
     */
    abstract protected function render(): void;
}
