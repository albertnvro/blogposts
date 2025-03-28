<?php
/*
Plugin Name: Último Post del Blog
Description: Obtiene y muestra el último post de un feed RSS y un grid de posts
Version: 1.4
Author: Albert Navarro
Author URI: https://www.linkedin.com/in/albert-n-579261256/
*/

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

class UltimoPostRSS {
    private $option_name = 'ultimo_post_rss_settings';

    public function __construct() {
        add_shortcode('ultimo_post_rss', [$this, 'shortcode_ultimo_post']);
        add_shortcode('posts_del_blog', [$this, 'shortcode_grid_posts']);
        add_action('wp_enqueue_scripts', [$this, 'agregar_estilos']);
        
        // Acciones para el menú de administración
        add_action('admin_menu', [$this, 'agregar_pagina_configuracion']);
        add_action('admin_init', [$this, 'registrar_configuraciones']);
    }

    public function agregar_estilos() {
        // Usar wp_add_inline_style para agregar estilos de manera segura
        wp_register_style('ultimo-post-rss-style', false);
        wp_enqueue_style('ultimo-post-rss-style');
        
        $custom_css = '
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            width: 100%;
        }

        .posts-grid-item {
            display: flex;
            flex-direction: column;
            border: 1px solid #e0e0e0;
            padding: 15px;
            border-radius: 5px;
            box-sizing: border-box;
            height: 100%;
            transition: box-shadow 0.3s ease;
        }

        .posts-grid-item:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .posts-grid-item-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .posts-grid-item h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.2em;
            line-height: 1.3;
        }

        .posts-grid-item h3 a {
            color: #000;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .posts-grid-item h3 a:hover {
            color: #555;
        }

        .posts-grid-item .fecha {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .posts-grid-item .extracto {
            color: #444;
            margin-bottom: 15px;
            flex-grow: 1;
        }

        .posts-grid-item .leer-mas {
            align-self: flex-start;
            display: inline-block;
            background-color: #000;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }

        .posts-grid-item .leer-mas:hover {
            background-color: #333;
        }

        .ultimo-post-rss {
            background-color: #f9f9f9;
            border-left: 4px solid #000;
            padding: 15px;
            margin: 15px 0;
        }

        .ultimo-post-rss h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        .ultimo-post-rss h3 a {
            color: #333;
            text-decoration: none;
        }

        .ultimo-post-rss .fecha {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .ultimo-post-rss .extracto {
            color: #444;
            margin-bottom: 10px;
        }

        .ultimo-post-rss .leer-mas {
            display: inline-block;
            background-color: #000;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .posts-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            .posts-grid-item {
                padding: 10px;
            }
        }';

        wp_add_inline_style('ultimo-post-rss-style', $custom_css);
    }

    public function agregar_pagina_configuracion() {
        add_options_page(
            'Configuración de Último Post RSS', 
            'Último Post RSS', 
            'manage_options', 
            'ultimo-post-rss', 
            [$this, 'pagina_configuracion']
        );
    }

    public function registrar_configuraciones() {
        register_setting('ultimo_post_rss_settings_group', $this->option_name);
    }

    public function pagina_configuracion() {
        ?>
        <div class="wrap">
            <h1>Configuración de Último Post RSS</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ultimo_post_rss_settings_group');
                do_settings_sections('ultimo_post_rss_settings_group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">URL del Feed RSS</th>
                        <td>
                            <input 
                                type="url" 
                                name="<?php echo $this->option_name; ?>[feed_url]" 
                                value="<?php echo esc_url($this->obtener_url_feed()); ?>" 
                                placeholder="https://ejemplo.com/feed/" 
                                class="regular-text"
                                required
                            />
                            <p class="description">
                                <strong>Requisitos:</strong>
                                <ul style="list-style-type: disc; padding-left: 20px;">
                                    <li>Debe ser un feed RSS de un sitio WordPress</li>
                                    <li>El feed debe ser públicamente accesible</li>
                                    <li>URL completa terminando en "/feed/"</li>
                                </ul>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <div class="card">
                <h2>Instrucciones de Uso</h2>
                <p><strong>Importante:</strong> Configure primero la URL del feed antes de usar los shortcodes.</p>
                <p>Shortcodes:</p>
                <ul style="list-style-type: disc; padding-left: 20px;">
                    <li><code>[ultimo_post_rss]</code>: Muestra el último post</li>
                    <li><code>[posts_del_blog]</code>: Muestra grid de posts</li>
                    <li><code>[posts_del_blog numero="3"]</code>: Personaliza el número de posts</li>
                </ul>
            </div>
        </div>
        <?php
    }

    public function obtener_url_feed() {
        $opciones = get_option($this->option_name);
        return !empty($opciones['feed_url']) ? $opciones['feed_url'] : '';
    }

    public function obtener_ultimo_post($feed_url) {
        // Cargar feed RSS
        $rss = fetch_feed($feed_url);

        // Verificar errores
        if (is_wp_error($rss)) {
            return false;
        }

        // Obtener items
        $maxitems = $rss->get_item_quantity(1);
        $rss_items = $rss->get_items(0, $maxitems);

        // Verificar si hay items
        if ($maxitems == 0) {
            return false;
        }

        // Obtener el primer item
        $item = $rss_items[0];

        return [
            'titulo' => $item->get_title(),
            'enlace' => $item->get_permalink(),
            'fecha' => $item->get_date('j F Y'),
            'descripcion' => $this->obtener_extracto($item->get_description())
        ];
    }

    public function obtener_posts($feed_url, $numero = 6) {
        // Cargar feed RSS
        $rss = fetch_feed($feed_url);

        // Verificar errores
        if (is_wp_error($rss)) {
            return false;
        }

        // Obtener items
        $maxitems = $rss->get_item_quantity($numero);
        $rss_items = $rss->get_items(0, $maxitems);

        // Verificar si hay items
        if ($maxitems == 0) {
            return false;
        }

        $posts = [];
        foreach ($rss_items as $item) {
            $posts[] = [
                'titulo' => $item->get_title(),
                'enlace' => $item->get_permalink(),
                'fecha' => $item->get_date('j F Y'),
                'descripcion' => $this->obtener_extracto($item->get_description())
            ];
        }

        return $posts;
    }

    public function obtener_extracto($texto, $longitud = 100) {
        $texto = strip_tags($texto);
        $texto = substr($texto, 0, $longitud);
        return $texto . '...';
    }

    public function shortcode_ultimo_post($atts) {
        // Obtener URL del feed
        $url = $this->obtener_url_feed();

        // Verificar si la URL está configurada
        if (empty($url)) {
            return '<p>Por favor, configure la URL del feed RSS en los ajustes del plugin.</p>';
        }

        // Obtener último post
        $post = $this->obtener_ultimo_post($url);

        // Verificar si se obtuvo el post
        if (!$post) {
            return '<p>No se pudo obtener el último post. Verifique la URL del feed en la configuración.</p>';
        }

        // Generar HTML
        $html = sprintf(
            '<div class="ultimo-post-rss">
                <h3><a href="%s">%s</a></h3>
                <p class="fecha">%s</p>
                <p class="extracto">%s</p>
                <a href="%s" class="leer-mas">Leer más</a>
            </div>',
            esc_url($post['enlace']),
            esc_html($post['titulo']),
            esc_html($post['fecha']),
            esc_html($post['descripcion']),
            esc_url($post['enlace'])
        );

        return $html;
    }

    public function shortcode_grid_posts($atts) {
        // Valores por defecto
        $atts = shortcode_atts([
            'numero' => 6
        ], $atts);

        // Obtener URL del feed
        $url = $this->obtener_url_feed();

        // Verificar si la URL está configurada
        if (empty($url)) {
            return '<p>Por favor, configure la URL del feed RSS en los ajustes del plugin.</p>';
        }

        // Obtener posts
        $posts = $this->obtener_posts($url, $atts['numero']);

        // Verificar si se obtuvieron posts
        if (!$posts) {
            return '<p>No se pudieron obtener los posts. Verifique la URL del feed en la configuración.</p>';
        }

        // Generar HTML del grid
        $html = '<div class="posts-grid">';
        foreach ($posts as $post) {
            $html .= sprintf(
                '<div class="posts-grid-item">
                    <div class="posts-grid-item-content">
                        <h3><a href="%s">%s</a></h3>
                        <p class="fecha">%s</p>
                        <p class="extracto">%s</p>
                        <a href="%s" class="leer-mas">Leer más</a>
                    </div>
                </div>',
                esc_url($post['enlace']),
                esc_html($post['titulo']),
                esc_html($post['fecha']),
                esc_html($post['descripcion']),
                esc_url($post['enlace'])
            );
        }
        $html .= '</div>';

        return $html;
    }
}

// Inicializar plugin
function iniciar_ultimo_post_rss() {
    new UltimoPostRSS();
}
add_action('plugins_loaded', 'iniciar_ultimo_post_rss');
