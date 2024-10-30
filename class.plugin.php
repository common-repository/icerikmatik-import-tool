<?php

class IcerikmatikPlugin {

    /**
     * @var string
     */
    private $action = '';

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var string
     */
    private $tmpDir = '';

    public function __construct() {
        if (!defined('ICERIKMATIK_INIT'))
            exit;

        $this->init();
    }

    /**
     * Initialize plugin
     *
     * @return void
     */
    public function init() {
        add_action('admin_menu', [$this, 'adminMenu']);

        $this->action = isset($_GET['action']) && $_GET['action'] ? $_GET['action'] : '';
        $this->options = get_option('icerikmatik_options', []);
        $this->api = new IcerikmatikApi($this->getOption('api_key'));

        $this->tmpDir = ICERIKMATIK_ABSPATH . DIRECTORY_SEPARATOR . 'tmp';
        if (!is_dir($this->tmpDir))
            mkdir($this->tmpDir);

    }

    /**
     * Menu action
     *
     * @return void
     */
    public function adminMenu() {
        add_menu_page('Projeler', 'Icerikmatik', 'edit_posts', 'icerikmatik.projects', [$this, 'projectsPage'], plugins_url('assets/icon-23x22.png', __FILE__), 75);
        add_submenu_page('icerikmatik.projects', 'Projeler', 'Projeler', 'edit_posts', 'icerikmatik.projects', [$this, 'projectsPage']);
        add_submenu_page('icerikmatik.projects', 'Ayarlar', 'Ayarlar', 'edit_posts', 'icerikmatik.settings', [$this, 'settingsPage']);
    }

    /**
     * Project list page
     */
    public function projectsPage() {
        $this->checkApiKey();

        $projectId = isset($_GET['project_id']) && (int) $_GET['project_id'] > 0 ? (int) $_GET['project_id'] : 0;
        if ($projectId)
            return $this->ordersPage($projectId);

        $projects = $this->api->getRequest('projects');
        return $this->getView('projects', compact('projects'));
    }

    /**
     * Order list page
     */
    protected function ordersPage($projectId) {
        $orderId = isset($_GET['order_id']) && (int) $_GET['order_id'] > 0 ? (int) $_GET['order_id'] : 0;
        if ($orderId)
            return $this->postsPage($projectId, $orderId);

        $project = $this->api->getRequest('project', ['project_id' => $projectId]);
        $orders = $this->api->getRequest('orders', ['project_id' => $projectId]);
        return $this->getView('orders', compact('orders', 'project'));
    }

    /**
     * List or import posts
     *
     * @param int $projectId
     * @param int $orderId
     */
    protected function postsPage($projectId, $orderId) {

        $ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'];

        //if post request, import posts
        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            $redirectUri = menu_page_url('icerikmatik.projects', false) . "&project_id={$projectId}&order_id={$orderId}";
            if ('import' != $this->arrayGet($_POST, 'action')) {
                $message = 'Bir eylem seçin';
                return $ajax ? $this->response(['status' => 'error', 'message' => $message]) : $this->addMessage($message, 'error')->redirect($redirectUri);
            }

            $posts = $this->arrayGet($_POST, 'posts');
            if (!$posts) {
                $message = 'İçeri aktarılacak en az bir içerik seçin';
                return $ajax ? $this->response(['status' => 'error', 'message' => $message]) : $this->addMessage($message, 'error')->redirect($redirectUri);
            }

            $category = (int) $this->arrayGet($_POST, 'category');
            if (!$category) {
                $message = 'İçeriklerin ekleneceği kategoriyi seçin';
                return $ajax ? $this->response(['status' => 'error', 'message' => $message]) : $this->addMessage($message, 'error')->redirect($redirectUri);
            }

            $postStatus = $this->arrayGet($_POST, 'status');
            if (!$postStatus || !in_array($postStatus, ['draft', 'publish'])) {
                $message = 'İçeriklerin durumunu seçin';
                return $ajax ? $this->response(['status' => 'error', 'message' => $message]) : $this->addMessage($message, 'error')->redirect($redirectUri);
            }

            $posts = $this->api->getRequest('posts', ['project_id' => $projectId, 'order_id' => $orderId, 'post_ids' => implode(',', $posts)]);

            if ('success' != $posts->status) {
                $message = 'Bir hatayla karşılaşıldı';
                return $ajax ? $this->response(['status' => 'error', 'message' => $message]) : $this->addMessage($message, 'error')->redirect($redirectUri);
            }

            foreach ($posts->data as $post) {

                $postId = wp_insert_post([
                    'post_title' => $post->title,
                    'post_content' => $post->content,
                    'post_category' => [$category],
                    'post_status' => $postStatus,
                    'meta_input' => ['im_post_id' => $post->id]
                ]);

                if (!isset($post->images) || !$postId) 
                    continue;

                foreach ($post->images as $image) {
                    $imageUri = $image;
                    if (!preg_match('/^https?:\/\//', $image)) {
                        $imageUri = $this->api->getRequestUri('post-image', [
                            'project_id' => $projectId,
                            'order_id' => $orderId,
                            'post_id' => $post->id,
                            'image' => basename($image)
                        ], false);
                    }

                    $attachId = $this->handleImage($imageUri);

                    $url = wp_get_attachment_url($attachId);

                    $post->content = preg_replace('/(<img[^>]+?)(?:src)\s?=\s?\"' . preg_quote($image, '/') . '\"/', '\1src="' . $url . '"', $post->content, 1);

                    $update = [
                        'ID' => $postId,
                        'post_content' => $post->content
                    ];

                    wp_update_post($update);
                }
            }

            $message = 'Ayarlar kaydedildi';
            return $ajax ? $this->response(['status' => 'success', 'message' => $message]) : $this->addMessage($message, 'success')->redirect($redirectUri);
        }


        $project = $this->api->getRequest('project', ['project_id' => $projectId]);
        $order = $this->api->getRequest('order', ['project_id' => $projectId, 'order_id' => $orderId]);
        $postTitles = $this->api->getRequest('post-titles', ['project_id' => $projectId, 'order_id' => $orderId]);

        if ('success' != $project->status || 'success' != $order->status || 'success' != $postTitles->status)
            return $this->addMessage('Hatalı istek', 'error')->redirect($redirectUri);

        foreach ($postTitles->data as &$postTitle) {
            $postTitle->exists = false;
            $post = new WP_Query("post_type=post&meta_key=im_post_id&meta_value={$postTitle->id}");
            if (!$post->have_posts())
                continue;

            $postTitle->exists = true;
        }

        return $this->getView('post-titles', compact('order', 'project', 'postTitles'));
    }

    /**
     * Settings page
     */
    public function settingsPage() {

        if ('POST' != $_SERVER['REQUEST_METHOD'])
            return $this->getView('settings', ['apiKey' => $this->getOption('api_key')]);

        $currentApiKey = $this->getOption('api_key');
        $apiKey = isset($_POST['api_key']) ? $_POST['api_key'] : '';
        if ($apiKey != $currentApiKey) {
            $this->options['api_key'] = $apiKey;
            $this->saveOptions();
        }
        return $this->addMessage('Ayarlar kaydedildi', 'success')->redirect(menu_page_url('icerikmatik.settings', false));
    }

    private function handleImage($url) {

        if (empty($url))
            return false;

        $uploadDir = wp_upload_dir();
        $image = file_get_contents($url);

        if (!$image)
            return false;

        $arrUrl = parse_url($url);
        $arrQuery = [];
        if (isset($arrUrl['query']))
            parse_str($arrUrl['query'], $arrQuery);

        if (!isset($arrQuery['image']) || !$arrQuery['image'])
            return false;

        $filename = $arrQuery['image'];

        if (wp_mkdir_p($uploadDir['path']))
            $file = $uploadDir['path'] . '/' . $filename;
        else
            $file = $uploadDir['basedir'] . '/' . $filename;

        file_put_contents($file, $image);

        $fileType = wp_check_filetype($filename, NULL);

        $attachment = array('post_mime_type' => $fileType['type'], 'post_title' => sanitize_file_name($filename), 'post_content' => '', 'post_status' => 'inherit');

        $attach_id = wp_insert_attachment($attachment, $file);

        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attach_data = wp_generate_attachment_metadata($attach_id, $file);

        wp_update_attachment_metadata($attach_id, $attach_data);
        return $attach_id;
    }

    /**
     * Check if api key present in database
     *
     * @return void
     */
    private function checkApiKey() {
        if (!$this->getOption('api_key'))
            $this->addMessage('Lütfen öncelikle api bilgilerinizi girin.')->redirect(menu_page_url('icerikmatik.settings', false));
    }

    /**
     * Get value from icerikmatik_options
     *
     * @return mixed
     */
    private function getOption($name, $default = null) {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    /**
     * Save plugin options
     *
     * @return bool
     */
    private function saveOptions() {
        return update_option('icerikmatik_options', $this->options);
    }

    /**
     * Show view script
     *
     * @return void
     */
    private function getView($name, array $params = []) {
        $filename = __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $name . '.php';

        if (!file_exists($filename))
            die("File not found: {$filename}");

        foreach ($params as $key => $value) {
            $$key = $value;
        }

        include $filename;
    }

    /**
     * Add flash message
     *
     * @return IcerikmatikPlugin
     */
    private function addMessage($message, $status = '') {
        if (!isset($_SESSION['icerikmatik']))
            $_SESSION['icerikmatik'] = [];

        if (!isset($_SESSION['icerikmatik']['messages']))
            $_SESSION['icerikmatik']['messages'] = [];

        $_SESSION['icerikmatik']['messages'][] = compact('status', 'message');

        return $this;
    }

    /**
     * Page redirect
     *
     * @return void
     */
    private function redirect($uri, $code = 302) {
        ob_clean();
        wp_redirect($uri, $code);
        exit();
    }

    /**
     * 
     */
    private function response($params, $output = 'json') {
        if ('json' == $output)
            $params = json_encode($params);

        ob_clean();
        echo $params;
        exit;
    }

    /**
     * Get flash messages
     *
     * @return array
     */
    private function getMessages($clear = true) {
        $messages = isset($_SESSION['icerikmatik']['messages']) ? $_SESSION['icerikmatik']['messages'] : [];
        if (true === $clear)
            unset($_SESSION['icerikmatik']['messages']);

        return $messages;
    }

    /**
     * Get array value
     *
     * @return mixed
     */
    private function arrayGet(array $array, $key, $default = null) {
        if (isset($array[$key]))
            return $array[$key];

        return $default;
    }
}
