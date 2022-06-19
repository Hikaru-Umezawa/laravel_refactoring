<?php

namespace App\Lib\LinkPreview;

use App\Lib\LinkPreview\GetLinkPreviewResponse;
use Dusterio\LinkPreview\Client;

final class LinkPreview
{
  /**
   * URLからメタ情報を取得する
   *
   * @param string $url
   * @return GetLinkPreviewResponse
   */
  public function get(string $url): GetLinkPreviewResponse
  {
    $previwClient = new Client($url);
    $resposnse = $previwClient->getPreview('general')->toArray();

    return new GetLinkPreviewResponse($resposnse['title'],$resposnse['description'],$resposnse['cover']);
  }
}
