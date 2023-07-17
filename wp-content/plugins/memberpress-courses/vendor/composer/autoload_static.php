<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitffb392cab4ecd4e0ae3347b39bdf3abf
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Svg\\' => 4,
            'Sabberworm\\CSS\\' => 15,
        ),
        'M' => 
        array (
            'Masterminds\\' => 12,
        ),
        'F' => 
        array (
            'FontLib\\' => 8,
        ),
        'D' => 
        array (
            'Dompdf\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Svg\\' => 
        array (
            0 => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg',
        ),
        'Sabberworm\\CSS\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabberworm/php-css-parser/src',
        ),
        'Masterminds\\' => 
        array (
            0 => __DIR__ . '/..' . '/masterminds/html5/src',
        ),
        'FontLib\\' => 
        array (
            0 => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib',
        ),
        'Dompdf\\' => 
        array (
            0 => __DIR__ . '/..' . '/dompdf/dompdf/src',
        ),
    );

    public static $classMap = array (
        'Dompdf\\Cpdf' => __DIR__ . '/..' . '/dompdf/dompdf/lib/Cpdf.php',
        'memberpress\\courses\\controllers\\App' => __DIR__ . '/../..' . '/app/controllers/App.php',
        'memberpress\\courses\\lib\\AttemptsTable' => __DIR__ . '/../..' . '/app/lib/AttemptsTable.php',
        'memberpress\\courses\\lib\\BaseBuiltinModel' => __DIR__ . '/../..' . '/app/lib/BaseBuiltinModel.php',
        'memberpress\\courses\\lib\\BaseCptCtrl' => __DIR__ . '/../..' . '/app/lib/BaseCptCtrl.php',
        'memberpress\\courses\\lib\\BaseCptModel' => __DIR__ . '/../..' . '/app/lib/BaseCptModel.php',
        'memberpress\\courses\\lib\\BaseCtaxCtrl' => __DIR__ . '/../..' . '/app/lib/BaseCtaxCtrl.php',
        'memberpress\\courses\\lib\\BaseCtrl' => __DIR__ . '/../..' . '/app/lib/BaseCtrl.php',
        'memberpress\\courses\\lib\\BaseModel' => __DIR__ . '/../..' . '/app/lib/BaseModel.php',
        'memberpress\\courses\\lib\\Config' => __DIR__ . '/../..' . '/app/lib/Config.php',
        'memberpress\\courses\\lib\\CreateException' => __DIR__ . '/../..' . '/app/lib/Exception.php',
        'memberpress\\courses\\lib\\CtaxModel' => __DIR__ . '/../..' . '/app/lib/BaseCtaxModel.php',
        'memberpress\\courses\\lib\\CtrlFactory' => __DIR__ . '/../..' . '/app/lib/CtrlFactory.php',
        'memberpress\\courses\\lib\\Db' => __DIR__ . '/../..' . '/app/lib/Db.php',
        'memberpress\\courses\\lib\\DbMigrationException' => __DIR__ . '/../..' . '/app/lib/Exception.php',
        'memberpress\\courses\\lib\\DbMigrationRollbackException' => __DIR__ . '/../..' . '/app/lib/Exception.php',
        'memberpress\\courses\\lib\\DeleteException' => __DIR__ . '/../..' . '/app/lib/Exception.php',
        'memberpress\\courses\\lib\\Exception' => __DIR__ . '/../..' . '/app/lib/Exception.php',
        'memberpress\\courses\\lib\\Inflector' => __DIR__ . '/../..' . '/app/lib/Inflector.php',
        'memberpress\\courses\\lib\\InvalidEmailException' => __DIR__ . '/../..' . '/app/lib/Exception.php',
        'memberpress\\courses\\lib\\InvalidMethodException' => __DIR__ . '/../..' . '/app/lib/Exception.php',
        'memberpress\\courses\\lib\\InvalidVariableException' => __DIR__ . '/../..' . '/app/lib/Exception.php',
        'memberpress\\courses\\lib\\LogException' => __DIR__ . '/../..' . '/app/lib/Exception.php',
        'memberpress\\courses\\lib\\UpdateException' => __DIR__ . '/../..' . '/app/lib/Exception.php',
        'memberpress\\courses\\lib\\Utils' => __DIR__ . '/../..' . '/app/lib/Utils.php',
        'memberpress\\courses\\lib\\Validate' => __DIR__ . '/../..' . '/app/lib/Validate.php',
        'memberpress\\courses\\lib\\ValidationException' => __DIR__ . '/../..' . '/app/lib/Exception.php',
        'memberpress\\courses\\tests\\UnitTestCase' => __DIR__ . '/../..' . '/tests/UnitTestCase.php',
        'memberpress\\courses\\tests\\factories\\Course' => __DIR__ . '/../..' . '/tests/factories/Course.php',
        'memberpress\\courses\\tests\\factories\\Lesson' => __DIR__ . '/../..' . '/tests/factories/Lesson.php',
        'memberpress\\courses\\tests\\factories\\Section' => __DIR__ . '/../..' . '/tests/factories/Section.php',
        'memberpress\\courses\\tests\\factories\\UnitTestFactory' => __DIR__ . '/../..' . '/tests/factories/UnitTestFactory.php',
        'memberpress\\courses\\tests\\factories\\UserProgress' => __DIR__ . '/../..' . '/tests/factories/UserProgress.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitffb392cab4ecd4e0ae3347b39bdf3abf::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitffb392cab4ecd4e0ae3347b39bdf3abf::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitffb392cab4ecd4e0ae3347b39bdf3abf::$classMap;

        }, null, ClassLoader::class);
    }
}
