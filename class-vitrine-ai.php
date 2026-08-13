<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * MVP: criar vitrines via chat com IA, depois editar no builder visual.
 */
class Vitrine_AI {

    const OPTION_API_KEY  = 'vitrine_ai_api_key';
    const OPTION_MODEL    = 'vitrine_ai_model';
    const OPTION_API_BASE = 'vitrine_ai_api_base';
    const OPTION_ENABLED  = 'vitrine_ai_enabled';

    public static function init() {
        if ( ! is_admin() ) {
            return;
        }

        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 9 );
        add_action( 'admin_init', array( __CLASS__, 'handle_settings_save' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'admin_head-edit.php', array( __CLASS__, 'render_list_page_button' ) );
        add_action( 'wp_ajax_vitrine_ai_chat', array( __CLASS__, 'ajax_chat' ) );
        add_action( 'wp_ajax_vitrine_ai_chat_stream', array( __CLASS__, 'ajax_chat_stream' ) );
        add_action( 'wp_ajax_vitrine_ai_suggestions', array( __CLASS__, 'ajax_suggestions' ) );
        add_action( 'wp_ajax_vitrine_ai_generate', array( __CLASS__, 'ajax_generate' ) );
    }

    public static function register_menu() {
        $enabled = self::is_enabled();

        if ( $enabled ) {
            add_submenu_page(
                'edit.php?post_type=vitrine',
                __( 'Criar com IA', 'builder-vitrine' ),
                __( 'Criar com IA', 'builder-vitrine' ),
                'edit_posts',
                'vitrine-ai-builder',
                array( __CLASS__, 'render_page' )
            );
            return;
        }

        // Temporariamente oculto: menu "Configurações IA" (reativar quando necessário).
        /*
        if ( current_user_can( 'manage_options' ) ) {
            add_submenu_page(
                'edit.php?post_type=vitrine',
                __( 'Configurações IA', 'builder-vitrine' ),
                __( 'Configurações IA', 'builder-vitrine' ),
                'manage_options',
                'vitrine-ai-builder',
                array( __CLASS__, 'render_page' )
            );
        }
        */
    }

    public static function handle_settings_save() {
        if ( empty( $_POST['vitrine_ai_settings_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vitrine_ai_settings_nonce'] ) ), 'vitrine_ai_settings' ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $enabled = ! empty( $_POST['vitrine_ai_enabled'] ) ? '1' : '0';
        $api_key = isset( $_POST['vitrine_ai_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['vitrine_ai_api_key'] ) ) : '';
        $model   = isset( $_POST['vitrine_ai_model'] ) ? sanitize_text_field( wp_unslash( $_POST['vitrine_ai_model'] ) ) : 'gpt-4o-mini';
        $base    = isset( $_POST['vitrine_ai_api_base'] ) ? esc_url_raw( wp_unslash( $_POST['vitrine_ai_api_base'] ) ) : '';

        update_option( self::OPTION_ENABLED, $enabled );
        update_option( self::OPTION_API_KEY, $api_key );
        update_option( self::OPTION_MODEL, $model ? $model : 'gpt-4o-mini' );
        update_option( self::OPTION_API_BASE, $base ? untrailingslashit( $base ) : '' );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'post_type'         => 'vitrine',
                    'page'              => 'vitrine-ai-builder',
                    'vitrine_ai_saved'  => '1',
                ),
                admin_url( 'edit.php' )
            )
        );
        exit;
    }

    public static function enqueue_assets( $hook ) {
        if ( 'vitrine_page_vitrine-ai-builder' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'dashicons' );

        wp_enqueue_style(
            'vitrine-ai-builder-css',
            VITRINE_URL . 'assets/css/ai-builder.css',
            array(),
            filemtime( VITRINE_PATH . 'assets/css/ai-builder.css' )
        );

        if ( ! self::is_enabled() ) {
            return;
        }

        wp_enqueue_script(
            'vitrine-ai-builder-js',
            VITRINE_URL . 'assets/js/ai-builder.js',
            array( 'jquery' ),
            filemtime( VITRINE_PATH . 'assets/js/ai-builder.js' ),
            true
        );

        wp_localize_script(
            'vitrine-ai-builder-js',
            'vitrineAiData',
            array(
                'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'vitrine_ai_generate' ),
                'streamEnabled' => function_exists( 'curl_init' ),
                'isConfigured'  => self::is_configured(),
                'canConfigure' => current_user_can( 'manage_options' ),
                'settingsUrl'  => admin_url( 'edit.php?post_type=vitrine&page=vitrine-ai-builder#vitrine-ai-settings' ),
                'logoUrl'      => self::get_logo_url(),
                'strings'      => array(
                    'placeholder'    => __( 'Descreva a vitrine que você quer criar…', 'builder-vitrine' ),
                    'send'           => __( 'Enviar', 'builder-vitrine' ),
                    'generate'       => __( 'Gerar vitrine e editar', 'builder-vitrine' ),
                    'generating'     => __( 'Gerando vitrine…', 'builder-vitrine' ),
                    'generatingHint' => __( 'Montando layout e criando rascunho. Isso pode levar alguns segundos.', 'builder-vitrine' ),
                    'thinking'       => __( 'Assistente está respondendo…', 'builder-vitrine' ),
                    'newChat'        => __( 'Nova conversa', 'builder-vitrine' ),
                    'errorGeneric'   => __( 'Não foi possível gerar a vitrine. Tente novamente.', 'builder-vitrine' ),
                    'errorChat'      => __( 'Não foi possível obter resposta da IA. Tente novamente.', 'builder-vitrine' ),
                    'errorNotConfig' => __( 'A API de IA ainda não foi configurada. Peça a um administrador para adicionar a chave.', 'builder-vitrine' ),
                    'welcomeTitle'   => __( 'Olá! Vamos montar sua vitrine?', 'builder-vitrine' ),
                    'welcomeText'    => __( 'Conte o objetivo da página, as seções desejadas e o conteúdo. Eu ajudo a organizar tudo — quando estiver pronto, clique em “Gerar vitrine e editar”.', 'builder-vitrine' ),
                    'assistantName'  => __( 'Assistente BVS', 'builder-vitrine' ),
                    'headerSubtitle' => __( 'Montador inteligente de vitrines', 'builder-vitrine' ),
                    'you'            => __( 'Você', 'builder-vitrine' ),
                    'suggestions'    => __( 'Sugestões', 'builder-vitrine' ),
                    'loadingSuggestions' => __( 'Gerando sugestões…', 'builder-vitrine' ),
                ),
                'examples'     => array(
                    __( 'Vitrine de lançamento: hero com título e descrição, grade com 6 benefícios e FAQ em toggle.', 'builder-vitrine' ),
                    __( 'Página de serviços: título, texto introdutório, carrossel com 4 depoimentos e botão “Fale conosco”.', 'builder-vitrine' ),
                    __( 'Landing simples: hero, três cards em grade e bloco de texto final com CTA.', 'builder-vitrine' ),
                ),
            )
        );
    }

    public static function render_page() {
        $enabled = self::is_enabled();

        if ( $enabled ) {
            if ( ! current_user_can( 'edit_posts' ) ) {
                wp_die( esc_html__( 'Sem permissão.', 'builder-vitrine' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'O modo chat com IA está desativado.', 'builder-vitrine' ) );
        }

        $api_key  = get_option( self::OPTION_API_KEY, '' );
        $model    = get_option( self::OPTION_MODEL, 'gpt-4o-mini' );
        $api_base = get_option( self::OPTION_API_BASE, '' );
        $page_title = $enabled
            ? __( 'Criar vitrine com IA', 'builder-vitrine' )
            : __( 'Configurações IA', 'builder-vitrine' );
        $page_lead = $enabled
            ? __( 'Converse com o assistente BVS, gere um rascunho e refine no editor visual.', 'builder-vitrine' )
            : __( 'Ative o modo chat para criar vitrines com IA. Enquanto estiver desligado, apenas esta tela de configuração fica disponível.', 'builder-vitrine' );
        ?>
        <div class="wrap vitrine-ai-wrap">
            <div class="vitrine-ai-page-head">
                <?php if ( self::get_logo_url() ) : ?>
                    <img src="<?php echo esc_url( self::get_logo_url() ); ?>" alt="<?php echo esc_attr__( 'Biblioteca Virtual em Saúde', 'builder-vitrine' ); ?>" class="vitrine-ai-page-logo" />
                <?php endif; ?>
                <div class="vitrine-ai-page-head__text">
                    <h1><?php echo esc_html( $page_title ); ?></h1>
                    <p class="vitrine-ai-lead">
                        <?php echo esc_html( $page_lead ); ?>
                    </p>
                </div>
            </div>

            <?php if ( current_user_can( 'manage_options' ) ) : ?>
                <details id="vitrine-ai-settings" class="vitrine-ai-settings"<?php echo ( $enabled && self::is_configured() ) ? '' : ' open'; ?>>
                    <summary><?php echo esc_html__( 'Configurações da IA', 'builder-vitrine' ); ?></summary>
                    <form method="post" class="vitrine-ai-settings-form">
                        <?php wp_nonce_field( 'vitrine_ai_settings', 'vitrine_ai_settings_nonce' ); ?>
                        <?php
                        if ( ! empty( $_GET['vitrine_ai_saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Configurações de IA salvas.', 'builder-vitrine' ) . '</p></div>';
                        }
                        settings_errors( 'vitrine_ai' );
                        ?>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php echo esc_html__( 'Modo chat', 'builder-vitrine' ); ?></th>
                                <td>
                                    <label for="vitrine_ai_enabled">
                                        <input type="checkbox" id="vitrine_ai_enabled" name="vitrine_ai_enabled" value="1" <?php checked( $enabled ); ?> />
                                        <?php echo esc_html__( 'Ativar criar com IA (modo chat)', 'builder-vitrine' ); ?>
                                    </label>
                                    <p class="description"><?php echo esc_html__( 'Quando desativado, o chat e o botão “Criar com IA” ficam ocultos. Só esta página de configuração permanece disponível para administradores.', 'builder-vitrine' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="vitrine_ai_api_key"><?php echo esc_html__( 'Chave da API', 'builder-vitrine' ); ?></label></th>
                                <td>
                                    <input type="password" id="vitrine_ai_api_key" name="vitrine_ai_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" autocomplete="off" />
                                    <p class="description"><?php echo esc_html__( 'Chave secreta da OpenAI (ou serviço compatível).', 'builder-vitrine' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="vitrine_ai_model"><?php echo esc_html__( 'Modelo', 'builder-vitrine' ); ?></label></th>
                                <td>
                                    <input type="text" id="vitrine_ai_model" name="vitrine_ai_model" value="<?php echo esc_attr( $model ); ?>" class="regular-text" placeholder="gpt-4o-mini" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="vitrine_ai_api_base"><?php echo esc_html__( 'URL base (opcional)', 'builder-vitrine' ); ?></label></th>
                                <td>
                                    <input type="url" id="vitrine_ai_api_base" name="vitrine_ai_api_base" value="<?php echo esc_attr( $api_base ); ?>" class="regular-text" placeholder="https://api.openai.com/v1" />
                                    <p class="description"><?php echo esc_html__( 'Deixe vazio para usar a OpenAI. Use para proxies compatíveis.', 'builder-vitrine' ); ?></p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button( __( 'Salvar configurações', 'builder-vitrine' ) ); ?>
                    </form>
                </details>
            <?php elseif ( ! self::is_configured() ) : ?>
                <div class="notice notice-warning"><p><?php echo esc_html__( 'A IA ainda não está configurada neste site.', 'builder-vitrine' ); ?></p></div>
            <?php endif; ?>

            <?php if ( $enabled ) : ?>
            <div id="vitrine-ai-app" class="vitrine-ai-app">
                <header class="vitrine-ai-header">
                    <?php if ( self::get_logo_url() ) : ?>
                        <img src="<?php echo esc_url( self::get_logo_url() ); ?>" alt="BVS" class="vitrine-ai-header__logo" />
                    <?php else : ?>
                        <span class="vitrine-ai-header__logo-fallback dashicons dashicons-superhero"></span>
                    <?php endif; ?>
                    <div class="vitrine-ai-header__info">
                        <strong class="vitrine-ai-header__title"><?php echo esc_html__( 'Assistente BVS', 'builder-vitrine' ); ?></strong>
                        <span class="vitrine-ai-header__subtitle"><?php echo esc_html__( 'Montador inteligente de vitrines', 'builder-vitrine' ); ?></span>
                    </div>
                    <span class="vitrine-ai-header__badge"><?php echo esc_html__( 'Online', 'builder-vitrine' ); ?></span>
                </header>
                <div class="vitrine-ai-body">
                <div id="vitrine-ai-messages" class="vitrine-ai-messages" aria-live="polite"></div>
                <div id="vitrine-ai-examples" class="vitrine-ai-examples"></div>
                <div class="vitrine-ai-composer">
                    <label for="vitrine-ai-input" class="screen-reader-text"><?php echo esc_html__( 'Sua mensagem', 'builder-vitrine' ); ?></label>
                    <textarea id="vitrine-ai-input" rows="3" placeholder="<?php echo esc_attr__( 'Descreva a vitrine que você quer criar…', 'builder-vitrine' ); ?>"></textarea>
                    <div class="vitrine-ai-composer-actions">
                        <button type="button" id="vitrine-ai-new-chat" class="button vitrine-ai-btn-secondary"><?php echo esc_html__( 'Nova conversa', 'builder-vitrine' ); ?></button>
                        <button type="button" id="vitrine-ai-send" class="button vitrine-ai-btn-send"><?php echo esc_html__( 'Enviar', 'builder-vitrine' ); ?></button>
                        <button type="button" id="vitrine-ai-generate" class="button button-primary vitrine-ai-btn-generate"><?php echo esc_html__( 'Gerar vitrine e editar', 'builder-vitrine' ); ?></button>
                    </div>
                </div>
                <div id="vitrine-ai-status" class="vitrine-ai-status" hidden></div>
                <div id="vitrine-ai-loading" class="vitrine-ai-loading" hidden aria-live="polite" aria-busy="false">
                    <div class="vitrine-ai-loading__panel" role="status">
                        <span class="vitrine-ai-loading__spinner" aria-hidden="true"></span>
                        <p class="vitrine-ai-loading__text"><?php echo esc_html__( 'Gerando vitrine…', 'builder-vitrine' ); ?></p>
                        <p class="vitrine-ai-loading__hint"><?php echo esc_html__( 'Montando layout e criando rascunho. Isso pode levar alguns segundos.', 'builder-vitrine' ); ?></p>
                    </div>
                </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Botão "Criar com IA" ao lado de "Adicionar nova" na listagem.
     */
    public static function render_list_page_button() {
        global $typenow;

        if ( ! self::is_enabled() || 'vitrine' !== $typenow || ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $url  = admin_url( 'edit.php?post_type=vitrine&page=vitrine-ai-builder' );
        $text = __( 'Criar com IA', 'builder-vitrine' );
        ?>
        <style>
            .page-title-action.vitrine-ai-title-action {
                background: #2271b1;
                border-color: #2271b1;
                color: #fff;
            }
            .page-title-action.vitrine-ai-title-action:hover {
                background: #135e96;
                border-color: #135e96;
                color: #fff;
            }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var heading = document.querySelector('.wrap > h1.wp-heading-inline');
            if (!heading) {
                return;
            }
            var link = document.createElement('a');
            link.href = <?php echo wp_json_encode( $url ); ?>;
            link.className = 'page-title-action vitrine-ai-title-action';
            link.textContent = <?php echo wp_json_encode( $text ); ?>;
            var addNew = heading.parentNode.querySelector('.page-title-action');
            if (addNew) {
                addNew.insertAdjacentElement('afterend', link);
            } else {
                heading.insertAdjacentElement('afterend', link);
            }
        });
        </script>
        <?php
    }

    public static function ajax_chat() {
        check_ajax_referer( 'vitrine_ai_generate', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'builder-vitrine' ) ) );
        }

        if ( ! self::is_enabled() ) {
            wp_send_json_error( array( 'message' => __( 'O modo chat com IA está desativado.', 'builder-vitrine' ) ) );
        }

        if ( ! self::is_configured() ) {
            wp_send_json_error( array( 'message' => __( 'API de IA não configurada.', 'builder-vitrine' ) ) );
        }

        $ai_messages = self::prepare_chat_messages_from_request();
        if ( is_wp_error( $ai_messages ) ) {
            wp_send_json_error( array( 'message' => $ai_messages->get_error_message() ) );
        }

        $response = self::call_chat_api(
            $ai_messages,
            array(
                'json_mode'   => false,
                'temperature' => 0.65,
            )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $message     = sanitize_textarea_field( $response );
        $suggestions = self::fetch_suggestions( $ai_messages, $message );

        wp_send_json_success(
            array(
                'message'     => $message,
                'suggestions' => $suggestions,
            )
        );
    }

    /**
     * Chat com streaming SSE (text/event-stream).
     */
    public static function ajax_chat_stream() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vitrine_ai_generate' ) ) {
            self::sse_send( array( 'type' => 'error', 'message' => __( 'Sessão expirada. Recarregue a página.', 'builder-vitrine' ) ) );
            exit;
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            self::sse_send( array( 'type' => 'error', 'message' => __( 'Permissão negada.', 'builder-vitrine' ) ) );
            exit;
        }

        if ( ! self::is_enabled() ) {
            self::sse_send( array( 'type' => 'error', 'message' => __( 'O modo chat com IA está desativado.', 'builder-vitrine' ) ) );
            exit;
        }

        if ( ! self::is_configured() ) {
            self::sse_send( array( 'type' => 'error', 'message' => __( 'API de IA não configurada.', 'builder-vitrine' ) ) );
            exit;
        }

        if ( ! function_exists( 'curl_init' ) ) {
            self::sse_send( array( 'type' => 'error', 'message' => __( 'Streaming indisponível neste servidor.', 'builder-vitrine' ) ) );
            exit;
        }

        $ai_messages = self::prepare_chat_messages_from_request();
        if ( is_wp_error( $ai_messages ) ) {
            self::sse_send( array( 'type' => 'error', 'message' => $ai_messages->get_error_message() ) );
            exit;
        }

        self::begin_sse();

        $full_text = '';
        $result    = self::stream_chat_api(
            $ai_messages,
            function ( $delta ) use ( &$full_text ) {
                $full_text .= $delta;
                self::sse_send(
                    array(
                        'type'    => 'delta',
                        'content' => $delta,
                    )
                );
            }
        );

        if ( is_wp_error( $result ) ) {
            self::sse_send( array( 'type' => 'error', 'message' => $result->get_error_message() ) );
            exit;
        }

        $message     = sanitize_textarea_field( $full_text );
        $suggestions = self::fetch_suggestions( $ai_messages, $message );

        self::sse_send(
            array(
                'type'        => 'done',
                'message'     => $message,
                'suggestions' => $suggestions,
            )
        );
        exit;
    }

    public static function ajax_suggestions() {
        check_ajax_referer( 'vitrine_ai_generate', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'builder-vitrine' ) ) );
        }

        if ( ! self::is_enabled() ) {
            wp_send_json_error( array( 'message' => __( 'O modo chat com IA está desativado.', 'builder-vitrine' ) ) );
        }

        if ( ! self::is_configured() ) {
            wp_send_json_error( array( 'message' => __( 'API de IA não configurada.', 'builder-vitrine' ) ) );
        }

        $messages_raw = isset( $_POST['messages'] ) ? wp_unslash( $_POST['messages'] ) : '';
        $messages     = json_decode( $messages_raw, true );
        $assistant    = isset( $_POST['assistant_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['assistant_message'] ) ) : '';

        if ( ! is_array( $messages ) ) {
            wp_send_json_error( array( 'message' => __( 'Mensagens inválidas.', 'builder-vitrine' ) ) );
        }

        $ai_messages = self::prepare_chat_messages_from_array( $messages );
        if ( is_wp_error( $ai_messages ) ) {
            wp_send_json_error( array( 'message' => $ai_messages->get_error_message() ) );
        }

        wp_send_json_success(
            array(
                'suggestions' => self::fetch_suggestions( $ai_messages, $assistant ),
            )
        );
    }

    /**
     * @return array|\WP_Error
     */
    private static function prepare_chat_messages_from_request() {
        $messages_raw = isset( $_POST['messages'] ) ? wp_unslash( $_POST['messages'] ) : '';
        $messages     = json_decode( $messages_raw, true );
        if ( ! is_array( $messages ) || empty( $messages ) ) {
            return new WP_Error( 'vitrine_ai_messages', __( 'Envie uma mensagem primeiro.', 'builder-vitrine' ) );
        }

        return self::prepare_chat_messages_from_array( $messages );
    }

    /**
     * @param array $messages Mensagens do frontend.
     * @return array|\WP_Error
     */
    private static function prepare_chat_messages_from_array( $messages ) {
        $messages = self::sanitize_chat_messages( $messages );
        if ( empty( $messages ) ) {
            return new WP_Error( 'vitrine_ai_messages', __( 'Mensagens inválidas.', 'builder-vitrine' ) );
        }

        $ai_messages = array(
            array(
                'role'    => 'system',
                'content' => self::build_chat_system_prompt(),
            ),
        );

        foreach ( $messages as $msg ) {
            $ai_messages[] = array(
                'role'    => $msg['role'],
                'content' => $msg['content'],
            );
        }

        return $ai_messages;
    }

    /**
     * @param array  $ai_messages Histórico enviado à API.
     * @param string $assistant_message Última resposta do assistente.
     * @return array
     */
    private static function fetch_suggestions( $ai_messages, $assistant_message ) {
        $assistant_message = trim( (string) $assistant_message );
        if ( '' === $assistant_message ) {
            return array();
        }

        $prompt_messages   = $ai_messages;
        $prompt_messages[] = array(
            'role'    => 'assistant',
            'content' => $assistant_message,
        );
        $prompt_messages[] = array(
            'role'    => 'user',
            'content' => 'Sugira exatamente 3 respostas curtas (máximo 90 caracteres cada) que o usuário poderia enviar em seguida para refinar a vitrine. JSON: {"suggestions":["","",""]}',
        );

        $response = self::call_chat_api(
            $prompt_messages,
            array(
                'json_mode'   => true,
                'temperature' => 0.5,
                'max_tokens'  => 220,
            )
        );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $parsed = self::parse_ai_json( $response );
        if ( is_wp_error( $parsed ) || empty( $parsed['suggestions'] ) || ! is_array( $parsed['suggestions'] ) ) {
            return array();
        }

        $suggestions = array();
        foreach ( $parsed['suggestions'] as $item ) {
            $text = sanitize_text_field( (string) $item );
            if ( '' !== $text ) {
                $suggestions[] = $text;
            }
            if ( count( $suggestions ) >= 3 ) {
                break;
            }
        }

        return $suggestions;
    }

    /**
     * Inicia resposta SSE.
     */
    private static function begin_sse() {
        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        if ( function_exists( 'ignore_user_abort' ) ) {
            ignore_user_abort( true );
        }

        nocache_headers();
        header( 'Content-Type: text/event-stream; charset=utf-8' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        header( 'Connection: keep-alive' );
        header( 'X-Accel-Buffering: no' );

        if ( function_exists( 'wp_ob_end_flush_all' ) ) {
            wp_ob_end_flush_all();
        }
    }

    /**
     * @param array $payload Dados do evento SSE.
     */
    private static function sse_send( $payload ) {
        echo 'data: ' . wp_json_encode( $payload ) . "\n\n";

        if ( function_exists( 'ob_flush' ) ) {
            @ob_flush();
        }
        flush();
    }

    /**
     * @param array    $messages Mensagens para a API.
     * @param callable $on_delta Callback por fragmento de texto.
     * @return true|\WP_Error
     */
    private static function stream_chat_api( $messages, $on_delta ) {
        $config = self::get_api_config();
        $body   = wp_json_encode(
            array(
                'model'       => $config['model'],
                'messages'    => $messages,
                'temperature' => 0.65,
                'stream'      => true,
            )
        );

        $buffer = '';

        $ch = curl_init();
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL            => $config['base'] . '/chat/completions',
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => array(
                    'Authorization: Bearer ' . $config['key'],
                    'Content-Type: application/json',
                ),
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_WRITEFUNCTION  => function ( $curl, $data ) use ( $on_delta, &$buffer ) {
                    unset( $curl );
                    $buffer .= $data;

                    while ( false !== ( $pos = strpos( $buffer, "\n" ) ) ) {
                        $line   = trim( substr( $buffer, 0, $pos ) );
                        $buffer = substr( $buffer, $pos + 1 );

                        if ( '' === $line || 0 !== strpos( $line, 'data:' ) ) {
                            continue;
                        }

                        $payload = trim( substr( $line, 5 ) );
                        if ( '[DONE]' === $payload ) {
                            continue;
                        }

                        $json = json_decode( $payload, true );
                        if ( ! is_array( $json ) ) {
                            continue;
                        }

                        if ( ! empty( $json['error']['message'] ) ) {
                            return strlen( $data );
                        }

                        $delta = isset( $json['choices'][0]['delta']['content'] )
                            ? (string) $json['choices'][0]['delta']['content']
                            : '';

                        if ( '' !== $delta ) {
                            $on_delta( $delta );
                        }
                    }

                    return strlen( $data );
                },
            )
        );

        $ok       = curl_exec( $ch );
        $err      = curl_error( $ch );
        $httpcode = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        if ( false === $ok ) {
            return new WP_Error( 'vitrine_ai_stream', $err ? $err : __( 'Falha no streaming.', 'builder-vitrine' ) );
        }

        if ( $httpcode < 200 || $httpcode >= 300 ) {
            return new WP_Error( 'vitrine_ai_stream', __( 'Erro na API de IA.', 'builder-vitrine' ) );
        }

        return true;
    }

    /**
     * @return array{key:string,model:string,base:string}
     */
    private static function get_api_config() {
        $base = get_option( self::OPTION_API_BASE, '' );
        if ( ! $base ) {
            $base = 'https://api.openai.com/v1';
        }

        return array(
            'key'   => trim( get_option( self::OPTION_API_KEY, '' ) ),
            'model' => get_option( self::OPTION_MODEL, 'gpt-4o-mini' ) ?: 'gpt-4o-mini',
            'base'  => untrailingslashit( $base ),
        );
    }

    public static function ajax_generate() {
        check_ajax_referer( 'vitrine_ai_generate', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'builder-vitrine' ) ) );
        }

        if ( ! self::is_enabled() ) {
            wp_send_json_error( array( 'message' => __( 'O modo chat com IA está desativado.', 'builder-vitrine' ) ) );
        }

        if ( ! self::is_configured() ) {
            wp_send_json_error( array( 'message' => __( 'API de IA não configurada.', 'builder-vitrine' ) ) );
        }

        $messages_raw = isset( $_POST['messages'] ) ? wp_unslash( $_POST['messages'] ) : '';
        $messages     = json_decode( $messages_raw, true );
        if ( ! is_array( $messages ) || empty( $messages ) ) {
            wp_send_json_error( array( 'message' => __( 'Descreva a vitrine antes de gerar.', 'builder-vitrine' ) ) );
        }

        $messages = self::sanitize_chat_messages( $messages );
        if ( empty( $messages ) ) {
            wp_send_json_error( array( 'message' => __( 'Mensagens inválidas.', 'builder-vitrine' ) ) );
        }

        $ai_messages = array(
            array(
                'role'    => 'system',
                'content' => self::build_system_prompt(),
            ),
        );

        foreach ( $messages as $msg ) {
            $ai_messages[] = array(
                'role'    => $msg['role'],
                'content' => $msg['content'],
            );
        }

        $ai_messages[] = array(
            'role'    => 'user',
            'content' => self::build_generate_instruction( $messages ),
        );

        $response = self::call_chat_api(
            $ai_messages,
            array(
                'json_mode'   => true,
                'temperature' => 0.4,
            )
        );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $parsed = self::parse_ai_json( $response );
        if ( is_wp_error( $parsed ) ) {
            wp_send_json_error( array( 'message' => $parsed->get_error_message() ) );
        }

        $parsed = self::refine_generated_layout( $parsed, $messages );

        $title  = ! empty( $parsed['title'] ) ? sanitize_text_field( $parsed['title'] ) : __( 'Vitrine gerada por IA', 'builder-vitrine' );
        $layout = isset( $parsed['layout'] ) ? $parsed['layout'] : array();
        if ( ! is_array( $layout ) || empty( $layout ) ) {
            wp_send_json_error( array( 'message' => __( 'A IA não retornou um layout válido.', 'builder-vitrine' ) ) );
        }

        $layout = Vitrine_Layout::normalize( $layout );
        if ( empty( $layout ) ) {
            wp_send_json_error( array( 'message' => __( 'Nenhum elemento válido foi gerado.', 'builder-vitrine' ) ) );
        }

        $post_id = wp_insert_post(
            array(
                'post_type'   => 'vitrine',
                'post_status' => 'draft',
                'post_title'  => $title,
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
        }

        update_post_meta( $post_id, '_vitrine_layout', $layout );

        if ( ! empty( $parsed['hero'] ) && is_array( $parsed['hero'] ) ) {
            $hero_partial = self::sanitize_hero_partial( $parsed['hero'] );
            if ( $hero_partial ) {
                $merged = Vitrine_Hero_Meta::merge_settings( $post_id, $hero_partial );
                update_post_meta( $post_id, Vitrine_Hero_Meta::META_KEY, $merged );
            }
        }

        wp_send_json_success(
            array(
                'post_id'  => $post_id,
                'edit_url' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
                'title'    => $title,
            )
        );
    }

    /**
     * URL do logo BVS para o chat.
     *
     * @return string
     */
    public static function get_logo_url() {
        $relative = 'assets/imagens/logoBVS.jpg';
        $path     = VITRINE_PATH . $relative;
        if ( ! file_exists( $path ) ) {
            return '';
        }
        return VITRINE_URL . $relative;
    }

    /**
     * Modo chat ativo? Padrão: desligado.
     *
     * @return bool
     */
    public static function is_enabled() {
        return '1' === (string) get_option( self::OPTION_ENABLED, '0' );
    }

    /**
     * @return bool
     */
    public static function is_configured() {
        $key = get_option( self::OPTION_API_KEY, '' );
        return is_string( $key ) && strlen( trim( $key ) ) > 10;
    }

    /**
     * @param array $messages Mensagens para a API.
     * @param array $args       json_mode, temperature.
     * @return string|\WP_Error
     */
    private static function call_chat_api( $messages, $args = array() ) {
        $args = wp_parse_args(
            $args,
            array(
                'json_mode'   => false,
                'temperature' => 0.7,
                'max_tokens'  => 0,
            )
        );

        $config = self::get_api_config();

        $body = array(
            'model'       => $config['model'],
            'messages'    => $messages,
            'temperature' => (float) $args['temperature'],
        );

        if ( ! empty( $args['json_mode'] ) ) {
            $body['response_format'] = array( 'type' => 'json_object' );
        }

        if ( ! empty( $args['max_tokens'] ) ) {
            $body['max_tokens'] = absint( $args['max_tokens'] );
        }

        $response = wp_remote_post(
            $config['base'] . '/chat/completions',
            array(
                'timeout' => 120,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $config['key'],
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $raw  = wp_remote_retrieve_body( $response );
        $data = json_decode( $raw, true );

        if ( $code < 200 || $code >= 300 ) {
            $message = __( 'Erro na API de IA.', 'builder-vitrine' );
            if ( is_array( $data ) && ! empty( $data['error']['message'] ) ) {
                $message = sanitize_text_field( $data['error']['message'] );
            }
            return new WP_Error( 'vitrine_ai_api', $message );
        }

        if ( ! is_array( $data ) || empty( $data['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'vitrine_ai_api', __( 'Resposta vazia da IA.', 'builder-vitrine' ) );
        }

        return (string) $data['choices'][0]['message']['content'];
    }

    /**
     * @param string $content Conteúdo retornado pela IA.
     * @return array|\WP_Error
     */
    private static function parse_ai_json( $content ) {
        $content = trim( (string) $content );

        if ( preg_match( '/```(?:json)?\s*(.*?)```/s', $content, $matches ) ) {
            $content = trim( $matches[1] );
        }

        $decoded = json_decode( $content, true );
        if ( ! is_array( $decoded ) ) {
            return new WP_Error( 'vitrine_ai_json', __( 'JSON inválido retornado pela IA.', 'builder-vitrine' ) );
        }

        return $decoded;
    }

    /**
     * @param array $messages Mensagens do frontend.
     * @return array
     */
    private static function sanitize_chat_messages( $messages ) {
        $clean = array();
        foreach ( $messages as $msg ) {
            if ( ! is_array( $msg ) || empty( $msg['role'] ) || ! isset( $msg['content'] ) ) {
                continue;
            }
            $role = 'assistant' === $msg['role'] ? 'assistant' : 'user';
            $text = sanitize_textarea_field( (string) $msg['content'] );
            if ( '' === $text ) {
                continue;
            }
            $clean[] = array(
                'role'    => $role,
                'content' => $text,
            );
        }
        return array_slice( $clean, -20 );
    }

    /**
     * @param array $hero Hero parcial da IA.
     * @return array
     */
    private static function sanitize_hero_partial( $hero ) {
        $map = array(
            'hero_text'        => 'sanitize_text_field',
            'hero_description' => 'sanitize_textarea_field',
            'hero_date'        => 'sanitize_text_field',
        );

        $partial = array();
        foreach ( $map as $key => $callback ) {
            if ( isset( $hero[ $key ] ) && '' !== (string) $hero[ $key ] ) {
                $partial[ $key ] = call_user_func( $callback, $hero[ $key ] );
                continue;
            }
            $short = str_replace( 'hero_', '', $key );
            if ( isset( $hero[ $short ] ) && '' !== (string) $hero[ $short ] ) {
                $partial[ $key ] = call_user_func( $callback, $hero[ $short ] );
            }
        }

        return $partial;
    }

    /**
     * @return string
     */
    private static function build_chat_system_prompt() {
        return 'Você é o Assistente BVS do Builder Vitrine (WordPress). Ajude o usuário a planejar vitrines em português (até ~8 frases). '
            . self::get_aranha_vocabulary_prompt()
            . 'Elementos também disponíveis: container, title, text, button, image, itemgrid, itemcarousel, toggle; hero opcional. '
            . 'Não gere JSON nesta conversa. Quando o plano estiver claro, peça para clicar em "Gerar vitrine e editar".';
    }

    /**
     * @param array $messages Histórico do chat.
     * @return string
     */
    private static function build_generate_instruction( $messages ) {
        $user_text = self::collect_user_text( $messages );
        $intent    = self::parse_aranha_intent( $user_text );

        $instruction = "Gere agora a vitrine completa em JSON conforme o schema. Responda SOMENTE com JSON válido.\n";

        if ( $intent['wants_aranha'] ) {
            $mode       = ( 'grade' === $intent['layout_mode'] ) ? 'grade' : 'circular';
            $mode_label = ( 'grade' === $mode ) ? 'Grade' : 'Circular';
            $instruction .= "\nO usuário pediu uma ARANHA. Use type \"aranha\" com settings.layout_mode \"{$mode}\" ({$mode_label}) como bloco principal.\n";
            $instruction .= "- Coloque o elemento aranha dentro de UM container na raiz (children).\n";
            $instruction .= "- \"elementos/itens/cards\" referem-se a settings.items dentro da aranha, NÃO a blocos separados no layout.\n";

            if ( $intent['item_count'] ) {
                $instruction .= '- Gere exatamente ' . $intent['item_count'] . " itens em settings.items, cada um com title, text e icon (dashicons-*).\n";
            }

            if ( $intent['only_aranha'] ) {
                $instruction .= "- A vitrine deve ter APENAS um container com só a aranha (sem title/text/grid extras).\n";
            }
        }

        return trim( $instruction );
    }

    /**
     * Texto de vocabulário sobre aranhas para os prompts.
     *
     * @return string
     */
    private static function get_aranha_vocabulary_prompt() {
        return 'IMPORTANTE — elemento ARANHA: quando o usuário disser "aranha", "uma aranha" ou "layout aranha", '
            . 'refere-se ao componente visual do builder (NÃO é animal). '
            . 'Use sempre type "aranha" com settings.layout_mode "circular" ou "grade". '
            . '"Aranha circular/orbital" = layout_mode circular (cards ao redor de imagem central). '
            . '"Aranha grade/moldura" = layout_mode grade (cards em moldura 3×3). '
            . 'Se não especificar grade, prefira circular. '
            . '"N elementos/itens/cards" = quantidade de cards em settings.items da aranha. '
            . '"Apenas/somente aranha" = vitrine só com esse bloco. ';
    }

    /**
     * @param array $messages Mensagens do chat.
     * @return string
     */
    private static function collect_user_text( $messages ) {
        $parts = array();
        foreach ( $messages as $msg ) {
            if ( isset( $msg['role'], $msg['content'] ) && 'user' === $msg['role'] ) {
                $parts[] = (string) $msg['content'];
            }
        }
        return implode( "\n", $parts );
    }

    /**
     * @param string $user_text Texto combinado do usuário.
     * @return array{wants_aranha:bool,layout_mode:string,item_count:int|null,only_aranha:bool}
     */
    private static function parse_aranha_intent( $user_text ) {
        $text = mb_strtolower( (string) $user_text, 'UTF-8' );

        $wants_aranha = (bool) preg_match( '/\baranha\b/u', $text );
        $only_aranha  = $wants_aranha && (
            (bool) preg_match( '/\b(apenas|somente|s[oó])\s+(a\s+)?aranha\b/u', $text )
            || (bool) preg_match( '/\baranha\s+(apenas|somente|s[oó])\b/u', $text )
            || (bool) preg_match( '/\b(apenas|somente|s[oó])\s+com\s+(a\s+)?aranha\b/u', $text )
        );
        $item_count   = null;

        if ( preg_match( '/(\d+)\s*(elementos|itens|cards)\b/u', $text, $matches ) ) {
            $item_count = max( 1, min( 12, (int) $matches[1] ) );
        }

        $layout_mode = 'circular';
        if ( preg_match( '/\baranha\s*grade\b|\bgrade\s*aranha\b|\bmoldura\b/u', $text ) ) {
            $layout_mode = 'grade';
        }

        return array(
            'wants_aranha' => $wants_aranha,
            'layout_mode'  => $layout_mode,
            'item_count'   => $item_count,
            'only_aranha'  => $only_aranha && $wants_aranha,
        );
    }

    /**
     * Corrige layout quando o usuário pediu aranha e a IA não interpretou bem.
     *
     * @param array $parsed   JSON decodificado.
     * @param array $messages Histórico.
     * @return array
     */
    private static function refine_generated_layout( $parsed, $messages ) {
        if ( empty( $parsed['layout'] ) || ! is_array( $parsed['layout'] ) ) {
            return $parsed;
        }

        $intent = self::parse_aranha_intent( self::collect_user_text( $messages ) );
        if ( ! $intent['wants_aranha'] ) {
            return $parsed;
        }

        $mode  = ! empty( $intent['layout_mode'] ) ? $intent['layout_mode'] : 'circular';
        $count = $intent['item_count'] ?: 7;

        if ( ! self::layout_contains_aranha( $parsed['layout'] ) ) {
            $parsed['layout'] = self::build_default_aranha_layout( $mode, $count );
        } else {
            self::adjust_aranha_items_in_layout( $parsed['layout'], $mode, $count );
        }

        if ( $intent['only_aranha'] || ( $intent['wants_aranha'] && $intent['item_count'] ) ) {
            $filtered = self::filter_layout_to_aranha_only( $parsed['layout'] );
            if ( ! empty( $filtered ) ) {
                $parsed['layout'] = $filtered;
            }
        }

        if ( empty( $parsed['title'] ) || __( 'Vitrine gerada por IA', 'builder-vitrine' ) === $parsed['title'] ) {
            $parsed['title'] = ( 'grade' === $mode )
                ? __( 'Vitrine Aranha Grade', 'builder-vitrine' )
                : __( 'Vitrine Aranha Circular', 'builder-vitrine' );
        }

        return $parsed;
    }

    /**
     * @param array $layout Layout.
     * @return bool
     */
    private static function layout_contains_aranha( $layout ) {
        foreach ( $layout as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            if ( isset( $item['type'] ) && in_array( $item['type'], array( 'aranha', 'aranha2', 'aranha3' ), true ) ) {
                return true;
            }
            if ( ! empty( $item['children'] ) && self::layout_contains_aranha( $item['children'] ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int    $index Índice do item.
     * @param string $mode  circular|grade.
     * @return array
     */
    private static function build_default_aranha_item( $index, $mode ) {
        $item = array(
            'title' => sprintf(
                /* translators: %d: item number */
                __( 'Elemento %d', 'builder-vitrine' ),
                $index
            ),
            'text'  => sprintf(
                /* translators: %d: item number */
                __( 'Descrição do elemento %d.', 'builder-vitrine' ),
                $index
            ),
            'icon'  => 'dashicons-admin-site',
            'link'  => '',
        );

        if ( 'grade' === $mode ) {
            $item['position'] = 'auto';
        }

        return $item;
    }

    /**
     * @param string $mode  circular|grade.
     * @param int    $count Itens.
     * @return array
     */
    private static function build_default_aranha_layout( $mode, $count ) {
        $mode  = ( 'grade' === $mode ) ? 'grade' : 'circular';
        $items = array();
        for ( $i = 1; $i <= $count; $i++ ) {
            $items[] = self::build_default_aranha_item( $i, $mode );
        }

        $settings = array(
            'layout_mode' => $mode,
            'items'       => $items,
        );

        if ( 'circular' === $mode ) {
            $settings['center_label'] = __( 'Centro', 'builder-vitrine' );
            $settings['center_size']  = '160';
            $settings['radius']       = '200';
            $settings['bg_color']     = '#f8f9fa';
        } else {
            $settings['center_size'] = '240';
            $settings['columns']     = '3';
            $settings['bg_color']    = '#f4f4f2';
        }

        return array(
            array(
                'type'     => 'container',
                'id'       => Vitrine_Layout::generate_id(),
                'settings' => array(
                    'direction' => 'column',
                    'padding'   => '24',
                    'bg_color'  => '#ffffff',
                ),
                'children' => array(
                    array(
                        'type'     => 'aranha',
                        'id'       => Vitrine_Layout::generate_id(),
                        'settings' => $settings,
                    ),
                ),
            ),
        );
    }

    /**
     * @param array  $layout Referência ao layout.
     * @param string $mode   circular|grade.
     * @param int    $count  Quantidade desejada.
     */
    private static function adjust_aranha_items_in_layout( &$layout, $mode, $count ) {
        $mode = ( 'grade' === $mode ) ? 'grade' : 'circular';
        foreach ( $layout as &$item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            if ( isset( $item['type'] ) && in_array( $item['type'], array( 'aranha', 'aranha2', 'aranha3' ), true ) ) {
                if ( ! isset( $item['settings'] ) || ! is_array( $item['settings'] ) ) {
                    $item['settings'] = array();
                }
                $item['type'] = 'aranha';
                $item['settings']['layout_mode'] = $mode;
                if ( ! isset( $item['settings']['items'] ) || ! is_array( $item['settings']['items'] ) ) {
                    $item['settings']['items'] = array();
                }

                while ( count( $item['settings']['items'] ) < $count ) {
                    $item['settings']['items'][] = self::build_default_aranha_item(
                        count( $item['settings']['items'] ) + 1,
                        $mode
                    );
                }

                if ( count( $item['settings']['items'] ) > $count ) {
                    $item['settings']['items'] = array_slice( $item['settings']['items'], 0, $count );
                }
            }

            if ( ! empty( $item['children'] ) ) {
                self::adjust_aranha_items_in_layout( $item['children'], $mode, $count );
            }
        }
    }

    /**
     * @param array $layout Layout.
     * @return array
     */
    private static function filter_layout_to_aranha_only( $layout ) {
        $result = array();

        foreach ( $layout as $item ) {
            if ( ! is_array( $item ) || 'container' !== ( $item['type'] ?? '' ) || empty( $item['children'] ) ) {
                continue;
            }

            $aranha_children = array();
            foreach ( $item['children'] as $child ) {
                if ( is_array( $child ) && isset( $child['type'] ) && in_array( $child['type'], array( 'aranha', 'aranha2', 'aranha3' ), true ) ) {
                    $aranha_children[] = $child;
                }
            }

            if ( empty( $aranha_children ) ) {
                continue;
            }

            $item['children'] = array( $aranha_children[0] );
            $result[]         = $item;
        }

        return $result;
    }

    /**
     * @return string
     */
    private static function build_system_prompt() {
        $schema = wp_json_encode( self::get_layout_schema(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

        return 'Você é um assistente que monta vitrines para o plugin WordPress Builder Vitrine. '
            . 'Responda SEMPRE com um único objeto JSON válido (sem markdown) neste formato:'
            . "\n{\n  \"title\": \"Título da vitrine\",\n  \"hero\": { \"text\": \"...\", \"description\": \"...\", \"date\": \"...\" },\n  \"layout\": [ ... ]\n}\n"
            . self::get_aranha_vocabulary_prompt()
            . "Regras do layout:\n"
            . "- \"layout\" é um array na raiz; cada item de topo DEVE ser type \"container\".\n"
            . "- Cada container tem \"settings\" e \"children\" (array de elementos).\n"
            . "- Para aranha: UM container na raiz com UM filho type \"aranha\" e settings.layout_mode \"circular\" ou \"grade\".\n"
            . "- Os cards da aranha ficam em settings.items (array), NÃO como blocos separados no layout.\n"
            . "- Gere IDs únicos no formato el_xxxxxxxx_timestamp.\n"
            . "- Use HTML simples em text.content (<p>, <strong>, <ul><li>).\n"
            . "- NÃO use html, shortcode ou video.\n"
            . "- Preencha textos realistas em português conforme o pedido do usuário.\n"
            . "- Para itemgrid/items e itemcarousel/items use objetos com title, description/text, image (URL vazia se não houver).\n"
            . "- Para toggle use settings.items com title e content.\n"
            . "Exemplo aranha circular com 3 itens:\n"
            . '{"title":"Minha Aranha","layout":[{"type":"container","id":"el_a1","settings":{"padding":"24"},"children":[{"type":"aranha","id":"el_a2","settings":{"layout_mode":"circular","center_label":"Centro","items":[{"title":"Item 1","text":"Texto 1","icon":"dashicons-star-filled","link":""},{"title":"Item 2","text":"Texto 2","icon":"dashicons-heart","link":""},{"title":"Item 3","text":"Texto 3","icon":"dashicons-yes","link":""}]}}]}]}'
            . "\nSchema resumido dos elementos:\n"
            . $schema;
    }

    /**
     * Schema compacto para a IA.
     *
     * @return array
     */
    private static function get_layout_schema() {
        return array(
            'container'    => array(
                'settings' => array( 'direction' => 'column|row', 'padding' => 'number', 'bg_color' => 'hex', 'gap' => 'number' ),
                'children' => 'array de elementos',
            ),
            'title'        => array( 'settings' => array( 'text' => 'string', 'tag' => 'h1-h3', 'align' => 'left|center', 'color' => 'hex', 'font_size' => 'number' ) ),
            'text'         => array( 'settings' => array( 'content' => 'html', 'align' => 'left|center' ) ),
            'button'       => array( 'settings' => array( 'text' => 'string', 'url' => 'string', 'align' => 'center', 'bg_color' => 'hex', 'color' => 'hex' ) ),
            'image'        => array( 'settings' => array( 'url' => 'string', 'align' => 'center', 'width' => 'percent' ) ),
            'itemgrid'     => array(
                'settings' => array(
                    'columns' => '1-4',
                    'items'   => array(
                        array( 'title' => 'string', 'description' => 'string', 'image' => 'url', 'link' => 'url' ),
                    ),
                ),
            ),
            'itemcarousel' => array(
                'settings' => array(
                    'slides_per_view_desktop' => '1-4',
                    'slides_per_view_tablet'  => '1-3',
                    'slides_per_view_mobile'  => '1-2',
                    'items'                   => array(
                        array( 'item_type' => 'image|icon', 'title' => 'string', 'text' => 'html', 'icon' => 'dashicons-*' ),
                    ),
                ),
            ),
            'toggle'       => array(
                'settings' => array(
                    'items' => array(
                        array( 'title' => 'string', 'content' => 'html' ),
                    ),
                ),
            ),
            'aranha'       => array(
                'label'    => 'Aranha — circular (orbital) ou grade (moldura)',
                'settings' => array(
                    'layout_mode'  => 'circular|grade',
                    'center_image' => 'url (opcional)',
                    'center_label' => 'string (circular; rótulo central se sem imagem)',
                    'center_size'  => 'number px',
                    'radius'       => 'number px orbital (circular)',
                    'columns'      => 'number referência (grade)',
                    'bg_color'     => 'hex',
                    'items'        => array(
                        array(
                            'title'    => 'string',
                            'text'     => 'string',
                            'icon'     => 'dashicons-* (opcional)',
                            'link'     => 'url (opcional)',
                            'position' => 'auto|top|bottom|left|right (grade)',
                        ),
                    ),
                ),
            ),
        );
    }
}
