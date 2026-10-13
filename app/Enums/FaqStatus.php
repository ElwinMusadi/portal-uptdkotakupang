<?php

namespace App\Enums;

/**
 * Publication lifecycle of a FAQ entry. Only published entries can reach the
 * public portal, and only together with explicit public intent and the
 * display toggle. Unpublished entries stay stored and editable.
 */
enum FaqStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Unpublished = 'unpublished';
}
