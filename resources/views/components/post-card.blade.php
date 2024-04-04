<x-card image="https://picsum.photos/500">
        <x-slot:date>
            {{$post->published_at->format('F j')}}
        </x-slot:date>
        <x-slot:time>
            {{$post->published_at->format('g:i A')}}
        </x-slot:time>
        <x-slot:admin>
            <a class='join-item btn btn-outline btn-xs border-t-0' href='/admin/posts/{{$post->id}}/edit'>Edit</a>
        </x-slot:admin>

        <h3 class='card-title'>{{$post->title}}</h3>
        <x-markdown class='prose-sm '>{{$post->content}}</x-markdown>
        <div class='card-actions' >
            <a href='/news/{{$post->id}}' class='btn btn-primary'>Read More</a>
        </div>
</x-card>
