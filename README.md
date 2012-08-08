Image processor

```php

'import' => array(
    'ext.image_processor.*',
    'ext.image_processor.image_handler.*',
 ),
'components' => array(
    'image' => array(
        'class'        => 'ext.image_processor.MImageProcessor',
        'imagePath'    => 'webroot.files.img', //save images to this path    
        'imageUrl'     => '/files/img/',
        'fileMode'     => 0777,
        'imageHandler' => array(
            'class'  => 'ext.image_processor.image_handler.MImageHandler',
            'driver' => 'MDriverImageMagic', // MDriverGD
        ),
        'forceProcess'       => false, // process image when we call getImageUrl if forceProcess = true
        'afterUploadProcess' => array(
            'condition' => array('maxWidth' => 800, 'maxHeight' => 600), // optional
            'actions'   => array(
                'resize' => array('width' => 800, 'height' => 600),
                //....
            )
        ),
        'presets' => array(
            'preset1' => array(
                'thumb' => array('width' => 100, 'height'  => 100)
            ),
            'preset2' => array(
                'resize' => array('width'  => 800, 'height' => 600),
                'flip'   => array('mode' => 1)
            ),
            //....
        ),
    ),
),

```
If forceProcess = false
we should include to main.php
'controllerMap' => array('image_processor' => 'ext.image_processor.MImageProcessorController'),

.htaccess example

AddDefaultCharset UTF-8
Options +FollowSymlinks
RewriteEngine on

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
# files/img/{namespace}/{preset}/{subDir}/{filename}
RewriteRule ^files/img/([^/]+)/([^/]+)/([^/]+)/([^/]+)$ image_processor/resize/n/$1/p/$2/d/$3/f/$4 [L]

# if a directory or a file exists, use it directly
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php 


add lines to the top index.php
 
if (isset($_SERVER['REDIRECT_URL']) && preg_match('/image_processor\/resize/i', $_SERVER['REDIRECT_URL'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['REDIRECT_URL'];
}

MImageBehavior

Behavior for managing image

Model

```php

public function behaviors()
{
    return array(
        'MImage' => array(
            'class'          => 'ext.image_processor.MImageBehavior',
            'imageProcessor' => 'image' // image processor component name 
        )
    );
}

echo $model->getImagePath('image', 'preset'); // preset = orig it is original file
echo $model->getImageUrl('image', 'preset', true);
$model->uploadImage(CUploadedFile::getInstance($model, 'image'), 'image');
$model->deleteImage('image'); or $model->deleteImage('image', 'preset');

public function actionCreate()
{
    $model = new Image;

    if (isset($_POST['Image'])) {
        $model->attributes = $_POST['Image'];
        if ($model->validate()) {
            $model->uploadImage(CUploadedFile::getInstance($model, 'image'), 'image');
            $model->save(false);
            $this->redirect(array('view', 'id' => $model->id));
        }
    }

    $this->render('create', array(
        'model' => $model,
    ));
}

```

MImageValidator

```php

public function rules()
{
    return array(
        // ....
        array('image', 'MImageValidator', 'types' => array('jpg', 'png', 'jpeg', 'gif'), 'minSize' => 1024, 'minWidth' => 1024, 'minHeight' => 2000),
        // ....
    );
}

```

MImageHandler supports two drivers: GD, ImageMagick

```php

'components' => array(
    'imageHandler' => array(
        'class'  => 'ext.image_processor.image_handler.MImageHandler',
        'driver' => 'MDriverImageMagic', // MDriverGD
        'driverOptions' => array(),
    ),
),

Yii::app()->imageHandler->load($file)->resize(100, 100)->show();

```