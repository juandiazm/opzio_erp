<?php 
namespace App\traits;

use \Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Str;
use Session;

use App\Models\blog;
use App\Models\blog_segment;
use App\Models\blog_email_subscriber;
use App\Models\blog_subject;
use App\Models\service;

trait blog_trait
{
    use 
    open_ia_trait
    ,string_helper_trait
    ,freepik_trait
    ,mail_trait
    ;
    private $URL_BLOG_SEGMENTS_PATH = 'images/blog/segment/';
    private $THREAD_SUBJECTS_BLOG_ID = 'thread_WUzfwPWIbz45DrsKQz5XgjeD';
    public function Blog_AddBlog(
        $title
        , $url
        , $brief
        , $author
        , $reduce_title
        , $keywords
        ){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            if($url == null){
                /*remove words with less than 3 characters*/
                $url = preg_replace('/\b\w{1,3}\b\s?/', '', $reduce_title);
                $url = explode(' ', $url);
                /*get first 4 words if the title has more than 4 words*/
                if(count($url)>4){
                    $url = $url[0].' '.$url[1].' '.$url[2] . ' ' . $url[3];
                }else{
                    $url = implode(' ', $url);
                }
                $url = Str::slug($url, '-');
            }
            /*check if url exists*/
            $url_exists = blog::where('url', $url)->first();
            if($url_exists){
                $url = $url . '-' . Str::random(5);
            }
            $Blog = new blog();
            $Blog->title = $title;
            $Blog->unique_id = Str::uuid()->toString();
            $Blog->url = $url;
            $Blog->brief = $brief;
            $Blog->author = $author;
            $Blog->reduce_title = $reduce_title;
            $Blog->keywords = $keywords;
            $Blog->save();
            $Response['status'] = 1;
            $Response['message'] = 'Blog agregado correctamente';
            $Response['data'] = $Blog;
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Blog_AddBlogByIA($title){
        $ResponseFunction = [
            'status' => 0,
            'message' => '',
            'data' => null
        ];
        try{
            $blog_content = [
                'title' => null
                , 'content' => null
                , 'keywords' => null
                , 'reduce_title' => null
                , 'brief' => null
            ];
            /*------------------------------------*/
            /*$thread = $this->OpenIA_AddThread();
            if($thread['status']==1){
                $thread = $thread['data'];
                $this->THREAD_SUBJECTS_BLOG_ID = $thread['thread_id'];
            }*/
            /*------------------------------------*/
            if($title == null){
                /*get random register for subject table*/
                $subject = blog_subject::inRandomOrder()->first();
                $Response = $this->OpenIA_MakeQuestionToAssistant(
                    $this->ES_BLOG_ASSISTANT_ID
                    , $this->THREAD_SUBJECTS_BLOG_ID
                    , 'Genera un título atractivo en español para un blog sobre '.$subject->name.', enfocado en el sector del desarrollo de software. Debe ser innovador, generar curiosidad, y provocar una necesidad en los lectores para que hagan clic.'
                    , 5
                    , 5
                );
                if($Response['status']==1){
                    $blog_content['title'] = $Response['data'][0];
                }
            }else{
                $blog_content['title'] = $title;
            }
            /*get the blog content*/
            if($blog_content['title'] != null){
                $thread = $this->OpenIA_AddThread();
                if($thread['status']==1){
                    $thread = $thread['data'];
                    /*Blog content*/
                    $Response = $this->OpenIA_MakeQuestionToAssistant(
                        $this->ES_BLOG_ASSISTANT_ID
                        , $thread['thread_id']
                        , 'Genera un artículo detallado en español sobre el tema '.$blog_content['title'].'. Devuelve la respuesta exclusivamente en formato HTML dentro del <body>, sin incluir <footer>.'
                        , 10
                        , 5
                    );
                    if($Response['status']==1){
                        $blog_content['content'] = $this->StringHelper_get_string_between(str_replace("\"", "'", str_replace("\n", "", $Response['data'][0])), '<body>', '</body>');
                        /*remove img tags*/
                        $blog_content['content'] = preg_replace('/<img[^>]*>/', '', $blog_content['content']);
                        /*remove a tags*/
                        $blog_content['content'] = preg_replace('/<a[^>]*>/', '', $blog_content['content']);
                        $blog_content['content'] = preg_replace('/<\/a[^>]*>/', '', $blog_content['content']);
                        /*remove button tags*/
                        $blog_content['content'] = preg_replace('/<button[^>]*>/', '', $blog_content['content']);
                        $blog_content['content'] = preg_replace('/<\/button[^>]*>/', '', $blog_content['content']);
                        /*Blog metadata keywords*/
                        $Response = $this->OpenIA_MakeQuestionToAssistant(
                            $this->ES_BLOG_ASSISTANT_ID
                            , $thread['thread_id']
                            , 'Genera 5 palabras clave que describan el blog, saparadas por coma'
                            , 3
                            , 5
                        );
                        if($Response['status']==1){
                            $blog_content['keywords'] = $Response['data'][0];
                            /*Mail title*/
                            $Response = $this->OpenIA_MakeQuestionToAssistant(
                                $this->ES_BLOG_ASSISTANT_ID
                                , $thread['thread_id']
                                , 'Responde unicamente con el titulo a un correo basado en el texto: '.$blog_content['title'].', debe ser atractivo, corto y generar clicks.'
                                , 3
                                , 5
                            );
                            if($Response['status']==1){
                                $blog_content['reduce_title'] = $Response['data'][0];
                                /*Mail content*/
                                $Response = $this->OpenIA_MakeQuestionToAssistant(
                                    $this->ES_BLOG_ASSISTANT_ID
                                    , $thread['thread_id']
                                    , 'Genera un resumen del blog para ser enviado por correo, el resumen debe ser atractivo y generar clicks.'
                                    , 3
                                    , 5
                                );
                                if($Response['status']==1){
                                    $blog_content['brief'] = $Response['data'][0];
                                    /////////////////////////////////////////////////////////
                                    $Response = $this->Blog_AddBlog(
                                        $blog_content['title']
                                        , null
                                        , $blog_content['brief']
                                        , 'Equipo de comunicaciones'
                                        , $blog_content['reduce_title']
                                        , $blog_content['keywords']
                                    );
                                    if($Response['status']==1){
                                        $blog = $Response['data'];
                                        /*select if freepick image or open ia image random*/
                                        $imageType = rand(0,1);
                                        if($imageType == 0){
                                            //get image from ia 
                                            $Response = $this->OpenIA_GenerateImage('Imagen sobre: "'.$blog_content['title'].'", usa colores pasteles en escala de azules, naranjas y grises, estilo 3D Vectorial, la imagen debe ser simple, no incluir ningún texto ni letras en la imagen.');
                                            if($Response['status']==1){
                                                $image_url =  collect($Response['data']['data'][0])['url'];
                                                /*store image from url*/
                                                $file_format = 'webp';
                                                $uid = str_replace('-','_',Str::uuid()->toString()).'.'.$file_format;
                                                $img = Image::make($image_url)->encode('webp', 90);
                                                Storage::disk('blog_principal_images')->put($uid, $img->stream());
                                                ImageOptimizer::optimize(Storage::disk('blog_principal_images')->path($uid));
                                                $blog->img = $uid;
                                                $blog->save();
                                            }
                                        }else{
                                            $IAPrompt =  $this->OpenIA_MakeQuestion(
                                                "Redacta un prompt que ayude a generar una imagen para una empresa de desarrollo de software, que no incluya texto, para Instagram que trate sobre '".$subject."', se quiere que la imagen sea de tipo animado, con pocos elementos, que incluya una persona de aspecto amigable y que use tonos azules y grises, redáctalo en inglés"
                                            );
                                            if($IAPrompt['status']==1){
                                                $IAPrompt = $IAPrompt['data'][0];
                                                $FreepickImage = $this->Freepik_GenerateImage(
                                                    $IAPrompt
                                                    ,'no text'
                                                    ,40
                                                    ,1
                                                    ,null
                                                    ,1
                                                    ,'squeare'
                                                    ,'vector'
                                                    ,'dramatic'
                                                    ,'volumetric'
                                                    ,'first-person'
                                                );
                                                if($FreepickImage['status']==1){
                                                    /*move freepik image to blog_principal_images*/
                                                    $file_format = 'webp';
                                                    $uid = str_replace('-','_',Str::uuid()->toString()).'.'.$file_format;
                                                    $img = Image::make($FreepickImage['data'])->encode('webp', 90);
                                                    Storage::disk('blog_principal_images')->put($uid, $img->stream());
                                                    ImageOptimizer::optimize(Storage::disk('blog_principal_images')->path($uid));
                                                    $blog->img = $uid;
                                                    $blog->save();
                                                    /*remove freepik image from url*/
                                                    $this->Freepik_RemoveImage($FreepickImage['uid']);
                                                    $blog->save();
                                                }
                                            }
                                        }
                                        $Response = $this->Blog_AddSegmentBlog(
                                            $blog->id
                                            , $blog_content['title']
                                            , 0
                                            , $blog_content['content']
                                            , null
                                            , 0
                                        );
                                        if($Response['status']==1){
                                            $ResponseFunction['status'] = 1;
                                            $ResponseFunction['data'] = [
                                                'blog' => $blog
                                                , 'segment' => $Response['data']
                                            ];
                                            
                                        }else{
                                            $ResponseFunction['message'] = 'No se pudo agregar el segmento del blog: '.$Response['message'];
                                        }
                                    }else{
                                        $ResponseFunction['message'] = 'No se pudo agregar el blog: '.$Response['message'];
                                    }
                                }else{
                                    $ResponseFunction['message'] = 'No se pudo obtener el contenido del correo: '.$Response['message'];
                                }
                            }else{
                                $ResponseFunction['message'] = 'No se pudo obtener el titulo del correo: '.$Response['message'];
                            }
                        }else{
                            $ResponseFunction['message'] = 'No se pudo obtener las palabras clave: '.$Response['message'];
                        }
                    }else{
                        $ResponseFunction['message'] = 'No se pudo obtener el contenido del blog: '.$Response['message'];
                    }
                }else{
                    $ResponseFunction['message'] = 'No se pudo crear el hilo de conversación: '.$thread['message'];
                }
            }else{
                $ResponseFunction['message'] = 'No se pudo obtener el titulo del blog: '.$Response['message'];
            }
        }catch(\Exception $e){
            info('Blog_AddBlogByIA error: '.$e->getMessage());
            $ResponseFunction['message'] = $e->getMessage();
        }
        return $ResponseFunction;
    }
    public function Blog_GetPageBlog(
        $lang,
        $pagination
    ){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $Blogs = blog::where('language', $lang)->where('approved', 1);
            if($pagination['search'] != null){
                $Blogs = $Blogs->where(function($query) use ($pagination){
                    $query->where('title', 'like', '%'.$pagination['search'].'%')
                    ->orWhere('brief', 'like', '%'.$pagination['search'].'%')
                    ->orWhere('author', 'like', '%'.$pagination['search'].'%')
                    ->orWhere('reduce_title', 'like', '%'.$pagination['search'].'%')
                    ;
                });
            }
            $pagination['total'] = $Blogs->count();
            $pagination['totalPages'] = ceil($pagination['total']/$pagination['size']);
            $Response['status'] = 1;
            $Response['message'] = 'Blog agregado correctamente';
            $Response['data'] = $Blogs->orderBy('id', 'desc')->skip($pagination['size']*($pagination['page']-1))->take($pagination['size'])->get();
            $Response['pagination'] = $pagination;
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Blog_EditBlog($id, $title, $url, $brief, $author){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $Blog = blog::find($id);
            if($Blog){
                $Blog->title = $title;
                $Blog->url = ($url == null?Str::slug($title, '-'):str_replace(' ', '', $url));
                $Blog->brief = $brief;
                $Blog->author = $author;
                $Blog->save();
                $Response['status'] = 1;
                $Response['message'] = 'Blog actualizado correctamente';
                $Response['data'] = $Blog;
            }else{
                $Response['message'] = 'Blog no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Blog_DeleteBlog($id){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $Blog = blog::find($id);
            if($Blog){
                $Segments = blog_segment::where('blog_id', $id)->get();
                foreach($Segments as $Segment){
                    $this->Blog_DeleteSegmentBlog($Segment->id);
                }
                Storage::disk('blog_principal_images')->delete($Blog->img);
                $Blog->delete();
                $Response['status'] = 1;
                $Response['message'] = 'Blog eliminado correctamente';
                $Response['data'] = $Blog;
            }else{
                $Response['message'] = 'Blog no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Blog_ChangePrincipalImageBlog($id, $img){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Blog = blog::find($id);
            if($Blog){
                if($img){
                    $file_format = strtolower($img->getClientOriginalExtension());
                    if(($file_format == 'png' || $file_format == 'gif' || $file_format == 'jpg' || $file_format == 'webp' || $file_format == 'jpeg')){
                        if($Blog->img){
                            Storage::disk('blog_principal_images')->delete($Blog->img);
                        }
                        $file_format = 'webp';
                        $uid = str_replace('-','_',Str::uuid()->toString()).'.'.$file_format;
                        $img = Image::make($img)->encode('webp', 90);
                        /*if($img->width()>100){
                            $img->resize(100, null, function ($constraint) {
                                $constraint->aspectRatio();
                            });
                        }*/
                        Storage::disk('blog_principal_images')->put($uid, $img->stream());
                        ImageOptimizer::optimize(Storage::disk('blog_principal_images')->path($uid));
                        $Blog->img = $uid;
                        $Blog->save();
                        $Response['status'] = 1;
                        $Response['message'] = 'Imagen principal actualizada correctamente';
                    }else{
                        $Response['message'] = 'Formato de imagen no permitido';
                    }
                }else{
                    $Response['message'] = 'Imagen no encontrada';
                }
                
            }else{
                $Response['message'] = 'Blog no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    function Blog_GetPrincipalInformationBlog($id){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $Blog = blog::find($id);
            if($Blog){
                $Response['status'] = 1;
                $Response['message'] = 'Información obtenida correctamente';
                $Response['data'] = $Blog;
            }else{
                $Response['message'] = 'Blog no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    function Blog_GetBlogByUrl($url){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $Blog = blog::with('blog_segments')->where('url', $url)->first();
            if($Blog){
                $Response['status'] = 1;
                $Response['message'] = 'Información obtenida correctamente';
                $Response['data'] = $Blog;
            }else{
                $Response['message'] = 'Blog no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    function Blog_AddSegmentBlog(
        $blog_id
        , $title
        , $title_position
        , $paragraph
        , $img
        , $img_position
    ){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $Blog = blog::find($blog_id);
            if($Blog){
                $BlogSegment = new blog_segment();
                $BlogSegment->blog_id = $blog_id;
                $BlogSegment->title = $title;
                $BlogSegment->title_position = $title_position;
                $BlogSegment->paragraph = $paragraph;
                $BlogSegment->img_position = $img_position;
                $BlogSegment->position = blog_segment::where('blog_id', $blog_id)->count()+1;
                if($img){
                    $file_format = strtolower($img->getClientOriginalExtension());
                    if(($file_format == 'png' || $file_format == 'gif' || $file_format == 'jpg' || $file_format == 'webp' || $file_format == 'jpeg')){
                        $file_format = 'webp';
                        $uid = str_replace('-','_',Str::uuid()->toString()).'.'.$file_format;
                        $img = Image::make($img)->encode('webp', 90);
                        /*if($img->width()>100){
                            $img->resize(100, null, function ($constraint) {
                                $constraint->aspectRatio();
                            });
                        }*/
                        $img->save($this->URL_BLOG_SEGMENTS_PATH . $uid);
                        ImageOptimizer::optimize($this->URL_BLOG_SEGMENTS_PATH . $uid);
                        $BlogSegment->img = $uid;
                    }else{
                        $Response['message'] = 'Formato de imagen no permitido';
                    }
                }
                $BlogSegment->save();
                $Response['status'] = 1;
                $Response['message'] = 'Segmento agregado correctamente';
                $Response['data'] = $BlogSegment;
            }else{
                $Response['message'] = 'Blog no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    function Blog_GetBlogSegments($blog_id){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $BlogSegment = blog_segment::where('blog_id', $blog_id)->orderBy('position', 'asc')->get();
            foreach($BlogSegment as $key => $value){
                $value->path = $this->URL_BLOG_SEGMENTS_PATH.$value->img;
            }
            $Response['status'] = 1;
            $Response['message'] = 'Segmentos obtenidos correctamente';
            $Response['data'] = $BlogSegment;
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    function Blog_UpPositionSegmentBlog($id){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $BlogSegment = blog_segment::find($id);
            if($BlogSegment){
                $BlogSegment->position = $BlogSegment->position-1;
                $BlogSegment->save();
                $BlogSegment = blog_segment::where('id', '!=',$BlogSegment->id)->where('blog_id', $BlogSegment->blog_id)->where('position', $BlogSegment->position)->first();
                if($BlogSegment){
                    $BlogSegment->position = $BlogSegment->position+1;
                    $BlogSegment->save();
                }
                $Response['status'] = 1;
                $Response['message'] = 'Posición actualizada correctamente';
                $Response['data'] = $BlogSegment;
            }else{
                $Response['message'] = 'Segmento no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    function Blog_DownPositionSegmentBlog($id){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $BlogSegment = blog_segment::find($id);
            if($BlogSegment){
                $BlogSegment->position = $BlogSegment->position+1;
                $BlogSegment->save();
                $BlogSegment = blog_segment::where('id', '!=',$BlogSegment->id)->where('blog_id', $BlogSegment->blog_id)->where('position', $BlogSegment->position)->first();
                if($BlogSegment){
                    $BlogSegment->position = $BlogSegment->position-1;
                    $BlogSegment->save();
                }
                $Response['status'] = 1;
                $Response['message'] = 'Posición actualizada correctamente';
                $Response['data'] = $BlogSegment;
            }else{
                $Response['message'] = 'Segmento no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    function Blog_DeleteSegmentBlog($id){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $BlogSegment = blog_segment::find($id);
            if($BlogSegment){
                \File::delete($this->URL_BLOG_SEGMENTS_PATH . $BlogSegment->img);
                $BlogSegment->delete();
                $Response['status'] = 1;
                $Response['message'] = 'Segmento eliminado correctamente';
            }else{
                $Response['message'] = 'Segmento no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    function Blog_EditSegmentBlog(
        $id
        , $title
        , $title_position
        , $paragraph
        , $img
        , $img_position
    ){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $BlogSegment = blog_segment::find($id);
            if($BlogSegment){
                $BlogSegment->title = $title;
                $BlogSegment->title_position = $title_position;
                $BlogSegment->paragraph = $paragraph;
                $BlogSegment->img_position = $img_position;
                if($img){
                    $file_format = strtolower($img->getClientOriginalExtension());
                    if(($file_format == 'png' || $file_format == 'gif' || $file_format == 'jpg' || $file_format == 'webp' || $file_format == 'jpeg')){
                        $delete_file = $this->URL_BLOG_SEGMENTS_PATH . $BlogSegment->img;
                        \File::delete($delete_file);
                        $file_format = 'webp';
                        $uid = str_replace('-','_',Str::uuid()->toString()).'.'.$file_format;
                        $img = Image::make($img)->encode('webp', 90);
                        /*if($img->width()>100){
                            $img->resize(100, null, function ($constraint) {
                                $constraint->aspectRatio();
                            });
                        }*/
                        $img->save($this->URL_BLOG_SEGMENTS_PATH . $uid);
                        ImageOptimizer::optimize($this->URL_BLOG_SEGMENTS_PATH . $uid);
                        $BlogSegment->img = $uid;
                    }else{
                        $Response['message'] = 'Formato de imagen no permitido';
                    }
                }
                $BlogSegment->save();
                $Response['status'] = 1;
                $Response['message'] = 'Segmento editado correctamente';
                $Response['data'] = $BlogSegment;
            }else{
                $Response['message'] = 'Segmento no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Blog_GetLastBlog(){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        $Response['data'] = null;
        try{
            $Blog = blog::orderBy('id', 'desc')->first();
            if($Blog){
                $Blog->path = $this->URL_BLOGS_PATH.$Blog->img;
                $Response['status'] = 1;
                $Response['message'] = 'Blog obtenido correctamente';
                $Response['data'] = $Blog;
            }else{
                $Response['message'] = 'Blog no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Blog_ApproveBlog(
        $unique_id
        ,$send_to_subscribers = false
    ){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Blog = blog::where('unique_id', $unique_id)->first();
            if($Blog){
                if($Blog->approved == 0){
                    if($send_to_subscribers == 'true'){
                        /*get the 10% of total blog_email_subscriber records random*/
                        $subscribers = blog_email_subscriber::where('is_active', 1)->inRandomOrder()->take(blog_email_subscriber::count()*0.06)->get();
                        $MailData = 
                        [
                            'subject' => $Blog->reduce_title
                        ];
                        $View = 'mail.blog_news';
                        foreach($subscribers as $subscriber){
                            $Mails = [];
                            $Mails[] = [
                                'address' => $subscriber['email'],
                                'name' => $subscriber['email']
                            ];
                            $ViewData = collect(
                            [
                                "subscriber" => $subscriber
                                , "blog" => $Blog
                            ]
                            );
                            $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null, null, 'news', ['address' => env('MAIL_NEWS_FROM_ADDRESS'), 'name' => env('MAIL_NEWS_FROM_NAME')]);
                        }
                        $Mails = [];
                        $Mails[] = [
                            'address' => 'soporte@ridder.com.co',
                            'name' => 'soporte@ridder.com.co'
                        ];
                        $ViewData = collect(
                        [
                            "subscriber" => null
                            , "blog" => $Blog
                        ]
                        );
                        $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null, null, 'news', ['address' => env('MAIL_NEWS_FROM_ADDRESS'), 'name' => env('MAIL_NEWS_FROM_NAME')]);
                        /////////////////////////
                    }
                    $Blog->approved = 1;
                    $Blog->save();
                    $Response['status'] = 1;
                    $Response['message'] = 'Blog aprobado correctamente';
                    $Response['data'] = $Blog;
                }else{
                    $Response['message'] = 'Blog ya aprobado';
                }
            }else{
                $Response['message'] = 'Blog no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    /*subjects*/
    public function Blog_SetSubjectBlogs($subjects){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            foreach($subjects as $subject){
                $Subject = blog_subject::where('name', $subject)->first();
                if(!$Subject){
                    $Subject = new blog_subject();
                    $Subject->unique_id = Str::uuid()->toString();
                    $Subject->name = $subject;
                    $Subject->save();
                }
            }
            $Response['status'] = 1;
            $Response['message'] = 'Temas agregados correctamente';
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Blog_GetSubjectBlog(){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Subjects = blog_subject::all();
            $Response['status'] = 1;
            $Response['message'] = 'Temas obtenidos correctamente';
            $Response['data'] = $Subjects;
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Blog_DeleteSubjectBlog(
        $unique_id
    ){
        $Response = array();
        $Response['status'] = 0;
        $Response['message'] = '';
        try{
            $Subject = blog_subject::where('unique_id', $unique_id)->first();
            if($Subject){
                $Subject->delete();
                $Response['status'] = 1;
                $Response['message'] = 'Tema eliminado correctamente';
            }else{
                $Response['message'] = 'Tema no encontrado';
            }
        }catch(\Exception $e){
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
}