<?php
declare(strict_types=1);

use Bitrix\Main\Context;
abstract class AbstractPage
{
    /**
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
     * @return void
     */
    protected function handlePost(): void
    {

    }

    /**
     * @return void
     */
    abstract protected function render(): void;
}
