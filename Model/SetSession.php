<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\VMContent\Model;

use Magento\Framework\App\State;
use Magento\Framework\App\Area;

class SetSession
{
    /** @var State */
    private $state;
    public function __construct(State $state)
    {
        try{
            $state->setAreaCode(Area::AREA_ADMINHTML);
        }
        catch(\Magento\Framework\Exception\LocalizedException $e){
            // left empty
        }
    }

}
