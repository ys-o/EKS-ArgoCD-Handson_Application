# EKS-ArgoCD-Handson_Application

## 概要

Web/APアプリケーションの資材用リポジトリです（PHP、Dockerfile、Nginx設定など）。
ローカルではDocker Compose、EKSではNginx・PHP-FPMとRDS MySQLを使って動作を確認します。

## 4リポジトリの役割

| リポジトリ | 役割 |
|---|---|
| [Terraform](https://github.com/ys-o/EKS-ArgoCD-Handson_Terraform) | AWSリソースの構築とArgo CDの初期導入 |
| [Argo CDアプリケーション定義](https://github.com/ys-o/EKS-ArgoCD-Handson_ArgoCD_app_of_apps) | Web/AP、ESO、AWS Load Balancer Controllerの子Application定義 |
| [Web/APマニフェスト](https://github.com/ys-o/EKS-ArgoCD-Handson_Application_manifests) | Deployment、Service、Ingress、SecretStore、ExternalSecret |
| [Web/APアプリ資材（本リポジトリ）](https://github.com/ys-o/EKS-ArgoCD-Handson_Application) | PHP、Dockerfile、Nginx設定、GitHub Actionsワークフロー |

## ファイル構成

```text
.github/workflows/
└── cicd.yaml                    # CI/CDワークフロー
app/
├── public/
│   ├── index.php                # 画面、/health、/ready
│   └── style.css                # 画面のスタイル
└── src/
    └── status.php               # DB接続確認
docker/
├── web/
│   ├── Dockerfile               # Nginxイメージ
│   ├── nginx.conf               # Nginxの基本設定
│   └── default.conf.template    # PHP-FPMへの転送設定
└── php/
    ├── Dockerfile               # PHP-FPMイメージ
    └── app.ini                  # PHP設定
tests/
├── smoke.sh                     # ローカルの疎通・障害時応答テスト
└── config.php                   # DB接続設定のテスト
compose.yaml                     # ローカルのWeb/AP/DB構成
.env.example                     # ローカル設定の記入例
```

## CI/CDパイプライン

このリポジトリにGitHub Actionsのワークフローを定義しています。
mainブランチへのpushを起点に、次の流れでアプリを更新します。

- CI-1：ビルドとテストを実行（PHP構文チェック、HTTP疎通チェック）。
- CI-2：CI-1が成功した場合、Web/APのイメージをビルドしてECRにpush。
- CD-1：CI-2が成功した場合、GitHub Appを使い、新しいイメージタグをWeb/APマニフェストのDeploymentに転記してpush。
- CD-2：Web/APマニフェストの変更をArgo CDが検知し、自動デプロイ。

※CD-2はワークフロー内ではなく、Argo CDによるマニフェストリポジトリの変更検知・同期で処理されます。
