# BBS(Bulletin Board System)
 
掲示板に自分のメッセージを投稿でき、他のユーザのメッセージに返信できるコミュニケーションツール。
## 掲示板の主な機能
- 会員登録
- ログイン/ログアウト
- 投稿/編集/削除
- 返信
- 退会

## プロジェクトの作成方法
1. Docker composeによるプロジェクトの起動  
    ```docker compose up -d```

## ファイル構造
<pre>
.
├── docker
│   ├── data
│   │   └── mysql
│   └── php
├── public
│   ├── join
│   └── member_image
└── sql
</pre>
