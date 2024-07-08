<?php

namespace Dormilich\RPSL\Attribute;

enum Presence
{
    case generated;
    case mandatory;
    case optional;
    case primary_key;
}
