WEB-APの資材用リポジトリです（php、Dockerfile等）  

このリポジトリにGitHub Workflowを定義し、CI/CDパイプラインを構築しました  
CI-1：ビルドとテストを実行（php構文チェック、HTTP疎通チェック）  
CI-2：CI-1が成功した場合、ECRにPUSH  
CD-1：CI-2が成功した場合、ECRにPUSHしたイメージのタグを、WEB-AP用のdeploymentマニフェストに転記  
（※）CD-2：WEB-AP用マニフェストの変更を検知し、自動デプロイ  
  
※CD-2は、ArgoCDによる、WEB-AP用マニフェストリポジトリの変更検知で処理されます  