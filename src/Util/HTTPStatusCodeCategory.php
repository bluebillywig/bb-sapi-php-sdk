<?php

namespace BlueBillywig\Util;

enum HTTPStatusCodeCategory
{
    case Informational;
    case Successful;
    case Redirection;
    case ClientError;
    case ServerError;
}
