<?php

final class OrderNotificationStates
{
    public const STATE_ACCEPTED = 'accepted';
    public const STATE_MISSING_ORDER = 'missing_order';
    public const STATE_MISSING_AUTH = 'missing_auth';
    public const STATE_NO_UPSERT = 'no_upsert';
    public const STATE_EXCEPTION = 'exception';

}