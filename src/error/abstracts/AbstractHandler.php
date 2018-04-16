<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 14:25
 */
namespace moon\error\abstracts;

use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractHandler{

    /**
     * @var array
     * */
    protected $ContentTypes = [
        'application/json',
        'application/xml',
        'text/xml',
        'text/html',
    ];

    /**
     * @param ServerRequestInterface $request
     * @return string
     * */
    protected function determineContentType(ServerRequestInterface $request)
    {
        $acceptHeader = $request->getHeaderLine('Accept');
        $selectedContentTypes = array_intersect(explode(',', $acceptHeader), $this->ContentTypes);

        if (count($selectedContentTypes)) {
            return current($selectedContentTypes);
        }

        // handle +json and +xml specially
        if (preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/' . $matches[1];
            if (in_array($mediaType, $this->ContentTypes)) {
                return $mediaType;
            }
        }

        return 'text/html';
    }
}