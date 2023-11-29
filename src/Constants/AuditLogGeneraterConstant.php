<?php

namespace Hankz\LaravelAuditLog\Constants;

class AuditLogGeneraterConstant
{
    /**
     * modify by create.
     */
    public const METHOD_CREATE = 1;
    public const METHOD_CREATE_NAME = 'create';

    /**
     * modify by update.
     */
    public const METHOD_UPDATE = 2;
    public const METHOD_UPDATE_NAME = 'update';


    /**
     * modify by delete.
     */
    public const METHOD_DELETE = 3;
    public const METHOD_DELETE_NAME = 'delete';

    /**
     * mapping change method name.
     */
    public const METHOD_MAP = [
        self::METHOD_CREATE => self::METHOD_CREATE_NAME,
        self::METHOD_UPDATE => self::METHOD_UPDATE_NAME,
        self::METHOD_DELETE => self::METHOD_DELETE_NAME,
    ];
}
