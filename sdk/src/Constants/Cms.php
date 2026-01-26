<?php
namespace Apo100l\Sdk\Constants;
interface Cms
{

    const YES_NO = [0 => 'Нет' , 1 => 'Да'];

    CONST PATTER_ID = ['id' => '[0-9]+'];
    const PER_PAGE = [100, 200, 500];

    const MESSAGE_ERROR = 'MESSAGE_ERROR';
    const MESSAGE_WARNING = 'MESSAGE_WARNING';

    const MESSAGE_SUCCESS = 'MESSAGE_SUCCESS';

    const MESSAGE_SYSTEM = 'MESSAGE_SYSTEM';

    const NODE = 'page';

    const NO_NAME = 'Без названия';

    const STATIC = 'static';

    const PAGE_MAIN = 'main';

    const PAGE_404 = '_404_';

    const BLOCK = 'block';

}