<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;

class CrawlerController extends Controller
{
    public function show(Request $request)
    {
        $va = Validator::make($request->all(), [
            'album_id' => 'int',
        ]);
        if ($va->fails()) {
            return response()->json(['result'=>$va->errors()],416);
        }
        $album_id= $request->album_id;
        $client = new Client();
        try {
            $response_origin = $client->request('GET', env('FB_BASE_URL').$album_id.'/photos?access_token='.env('ACCESS_TOKEN'));
        } catch (\Throwable $th) {
            return response()->json(['result'=>$th],500);
        }     
        //$response
        $response = json_decode($response_origin->getBody());
        $out = new \Symfony\Component\Console\Output\ConsoleOutput();
        $next="";
        $download = false;
        do {
            $next=false;
            foreach ($response->data as $menu){
            
                $output = preg_split( "/[\s\n,]+/", $menu->name );
                // try {
                //     mkdir($output[0]);
                // } catch (\Throwable $th){
                //     continue;
                // }
                
                
                // $menu_img_origin = $client->request('GET', env('FB_BASE_URL').$menu->id.'?fields=images&access_token='.env('ACCESS_TOKEN'));
                // $menu_img = json_decode($menu_img_origin->getBody())->images;
                // $the_img = $menu_img[0]->source;
                // $imgName = $output[0].'/menu.jpg';
                // file_put_contents($imgName, file_get_contents($the_img));
                
                
                $all_comments_origin = $client->request('GET', env('FB_BASE_URL').$menu->id.'?fields=comments&access_token='.env('ACCESS_TOKEN'));
                $all_comments = json_decode($all_comments_origin->getBody());
                
                if (!array_key_exists("comments",$all_comments)) {
                    continue;
                }
                $all_comments_data = $all_comments->comments->data;
                
                foreach (array_slice($all_comments_data,1) as $comment){
                    // $souceImg=$comment->message."   ".$comment->id;
                                     
                    if (!$download) {
                        if ($comment->id !== "1899205933474779_1899283883466984") {
                            continue;
                        } else{
                            $out->writeln("start download".$output[0]);
                            $download=true;
                        } 
                    } else{
                        $comment_attachment_origin = $client->request('GET', env('FB_BASE_URL').$comment->id.'/?fields=attachment&access_token='.env('ACCESS_TOKEN'));
                        $comment_attachment = json_decode($comment_attachment_origin->getBody());
                        try {
                            $souceImg=$comment_attachment->attachment->media->image->src;
                            $imgName = $output[0].'/'.$comment->message.'.jpg';
                            file_put_contents($imgName, file_get_contents($souceImg));
                        } catch (\Throwable $th) {
                            continue;
                        }
                    }
                    
                }
            
            }
            try {
                if ($response->paging->next) {
                    # code...
                    try {
                        $response_origin = $client->request('GET', $response->paging->next);
                    } catch (\Throwable $th) {
                        return response()->json(['result'=>$th],500);
                    }
                    $response = json_decode($response_origin->getBody());
                    $next=true;
                }
            } catch (\Throwable $th){
                return response()->json(['result'=>"end"],200);
            }
        } while ($next);
    
        
        // dd($response);
        // file_put_contents($imgName, file_get_contents($souceImg));
        return response()->json(['result'=>$response],200);
    }
}
