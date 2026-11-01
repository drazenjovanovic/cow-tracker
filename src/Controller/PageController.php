<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\SessionInterface;
use App\Core\View;
use App\Domain\EventAction;
use App\Domain\EventTarget;
use App\Service\ActivityLogger;

final class PageController extends Controller
{
    private const FLASH_COW_BOUGHT = 'cow_bought';

    public function __construct(
        View $view,
        private readonly SessionInterface $session,
        private readonly ActivityLogger $logger,
        private readonly string $thankYou,
        private readonly string $downloadFile,
        private readonly string $downloadName,
    ) {
        parent::__construct($view);
    }

    public function home(Request $request): Response
    {
        return $this->render('home', ['title' => 'Home']);
    }

    public function pageA(Request $request): Response
    {
        // Without JavaScript the buy button posts and redirects back here; that return trip is the
        // continuation of one interaction, not a new page view, so it isn't counted again.
        $bought = (bool) $this->session->pull(self::FLASH_COW_BOUGHT, false);
        if (!$bought) {
            $this->logger->log($request, EventAction::ViewPage, EventTarget::PageA);
        }

        return $this->render('pages/a', [
            'title' => 'Page A',
            'bought' => $bought,
            'thankYou' => $this->thankYou,
        ]);
    }

    public function buyCow(Request $request): Response
    {
        $this->logger->log($request, EventAction::ButtonClick, EventTarget::BuyCow);

        if ($request->wantsJson()) {
            return Response::json([
                'ok' => true,
                'message' => $this->thankYou,
            ]);
        }

        $this->session->set(self::FLASH_COW_BOUGHT, true);

        return Response::redirect('/page-a');
    }

    public function pageB(Request $request): Response
    {
        $this->logger->log($request, EventAction::ViewPage, EventTarget::PageB);

        return $this->render('pages/b', ['title' => 'Page B']);
    }

    public function download(Request $request): Response
    {
        if ($this->downloadFile === '' || !is_file($this->downloadFile) || !is_readable($this->downloadFile)) {
            throw new HttpException(404, 'The download is currently not available.');
        }

        // Only count the click once we know the file can actually be served.
        $this->logger->log($request, EventAction::ButtonClick, EventTarget::Download);

        return Response::download($this->downloadFile, $this->downloadName);
    }
}
