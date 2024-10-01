<?php 
namespace OSW3\Media\Trait\Entity;

use OSW3\Media\Trait\Entity\Property\UuidTrait;
use OSW3\Media\Trait\Entity\Property\MediaAliasesTrait;
use OSW3\Media\Trait\Entity\Property\OriginalSizeTrait;
use OSW3\Media\Trait\Entity\Property\MediaBasenameTrait;
use OSW3\Media\Trait\Entity\Property\MediaFilenameTrait;
use OSW3\Media\Trait\Entity\Property\MediaProviderTrait;
use OSW3\Media\Trait\Entity\Property\OriginalBasenameTrait;
use OSW3\Media\Trait\Entity\Property\OriginalFileHashTrait;
use OSW3\Media\Trait\Entity\Property\OriginalFilenameTrait;
use OSW3\Media\Trait\Entity\Property\OriginalMimetypeTrait;
use OSW3\Media\Trait\Entity\Property\OriginalExtensionTrait;

trait MediaTrait
{
    use UuidTrait;

    use OriginalFileHashTrait;
    use OriginalBasenameTrait;
    use OriginalFilenameTrait;
    use OriginalMimetypeTrait;
    use OriginalExtensionTrait;
    use OriginalSizeTrait;

    use MediaProviderTrait;
    use MediaBasenameTrait;
    use MediaFilenameTrait;
    use MediaAliasesTrait;
}