/*
 Navicat Premium Data Transfer

 Source Server         : qsy
 Source Server Type    : MySQL
 Source Server Version : 50726
 Source Host           : localhost:3306
 Source Schema         : qsy

 Target Server Type    : MySQL
 Target Server Version : 50726
 File Encoding         : 65001

 Date: 10/08/2024 01:57:39
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for analysis
-- ----------------------------
DROP TABLE IF EXISTS `analysis`;
CREATE TABLE `analysis`  (
  `vid` int(11) NOT NULL AUTO_INCREMENT COMMENT '解析id',
  `uid` int(11) NOT NULL COMMENT '用户id',
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '解析链接',
  `status` int(1) NOT NULL COMMENT '0：成功，1：失败',
  `addtime` varchar(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '解析时间',
  PRIMARY KEY (`vid`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '解析记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for config
-- ----------------------------
DROP TABLE IF EXISTS `config`;
CREATE TABLE `config`  (
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '配置项',
  `val` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '配置值',
  `desc` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '注释',
  PRIMARY KEY (`name`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

INSERT INTO `config` VALUES ('ispc', '0', '是否允许pc访问：0允许，1禁止');
INSERT INTO `config` VALUES ('videoid', 'adunit-26493e7b62a57305', '视频广告ID');
INSERT INTO `config` VALUES ('bannerid', 'adunit-62bf83481fda02b9', 'Banner广告ID');
INSERT INTO `config` VALUES ('chapinid', 'adunit-6762c6266071c786', '插屏广告ID');
INSERT INTO `config` VALUES ('openad', '0', '是否开启广告：0开启，1关闭');
INSERT INTO `config` VALUES ('jiliid', 'adunit-0334174d406d78e3', '激励广告ID');
INSERT INTO `config` VALUES ('notice', '', '公告');

-- ----------------------------
-- Table structure for domin
-- ----------------------------
DROP TABLE IF EXISTS `domin`;
CREATE TABLE `domin`  (
  `did` int(11) NOT NULL AUTO_INCREMENT,
  `domin` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `addtime` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`did`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for integral
-- ----------------------------
DROP TABLE IF EXISTS `integral`;
CREATE TABLE `integral`  (
  `jid` int(11) NOT NULL AUTO_INCREMENT COMMENT '积分id',
  `uid` int(11) NOT NULL COMMENT '用户id',
  `num` int(11) NOT NULL COMMENT '加减积分数值',
  `type` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '1：激励视频，2：管理操作，3：解析扣减，4：每日领取，5：邀请奖励，6：文案生成',
  `addtime` varchar(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '操作时间',
  PRIMARY KEY (`jid`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '积分操作表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for link
-- ----------------------------
DROP TABLE IF EXISTS `link`;
CREATE TABLE `link`  (
  `yid` int(11) NOT NULL AUTO_INCREMENT,
  `yname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '小程序名称',
  `ydesc` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '描述',
  `sort` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0' COMMENT '排序',
  `appid` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '小程序appid',
  `logo` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '头像',
  `num` int(11) NULL DEFAULT 0 COMMENT '点击次数',
  `status` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0' COMMENT '状态0显示1隐藏',
  `uptime` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '更新时间',
  `addtime` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '添加时间',
  `path` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '跳转的页面路径可带参数',
  PRIMARY KEY (`yid`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '小程序友情跳转' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`  (
  `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `pid` int(11) NULL DEFAULT NULL COMMENT '上级id',
  `openid` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '微信openid',
  `integral` int(11) NULL DEFAULT 20 COMMENT '积分',
  `ip` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户ip',
  `status` int(1) NOT NULL DEFAULT 0 COMMENT '状态，0：正常，1：封禁',
  `lastlogin` varchar(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `addtime` varchar(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '注册时间',
  `utype` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户类型，1管理，0普通',
  PRIMARY KEY (`uid`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for wen
-- ----------------------------
DROP TABLE IF EXISTS `wen`;
CREATE TABLE `wen`  (
  `wid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '标题',
  `desc` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `type` int(1) NULL DEFAULT NULL COMMENT '类型：0伪原创，1小红书，2抖音，3朋友圈',
  `icon` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '图片地址',
  `intitle` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '请求输入框标题',
  `indesc` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '请求输入框提示',
  `otitle` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '返回框标题',
  `odesc` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '返回框提示，用于显示示例',
  `status` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0' COMMENT '状态',
  `sort` varchar(2) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0' COMMENT '排序',
  `action` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '内置的指令，比如：生成一篇爆款小红书文案',
  PRIMARY KEY (`wid`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

INSERT INTO `wen` (`wid`, `title`, `desc`, `type`, `icon`, `intitle`, `indesc`, `otitle`, `odesc`, `status`, `sort`, `action`) VALUES
(1, '小红薯爆款文案', '根据你的主题生成小红薯种草文案', 1, 'https://域名/static/wen/txhs.png', '主题/要求', '输入主题和要求，可参考下方示例', '文案/示例', '主题：聚合去水印工具小程序\n<br>种草原因：免费，好用，支持多平台\n<br>字数：150\n<br>语气：正式', '0', '5', '写一篇小红书爆款文案'),
(2, '抖音爆款文案', '根据你的主题生成抖音爆款种文案', 2, 'https://域名/static/wen/tdy.png', '主题/要求', '输入主题和要求，可参考下方示例', '文案/示例', '主题：聚合去水印工具小程序\n<br>种草原因：免费好用，支持多平台\n<br>字数：300\n<br>语气：幽默', '0', '4', '写一篇抖音爆款文案'),
(3, '朋友圈文案', '根据你的主题生成朋友圈文案', 3, 'https://域名/static/wen/tpyq.png', '主题/要求', '输入主题和要求，可参考下方示例', '文案/示例', '主题：聚合去水印工具小程序\n<br>种草原因：免费好用，支持多平台\n<br>字数：200\n<br>语气：幽默', '0', '2', '写一篇朋友圈爆款文案'),
(4, '文案改写', '伪原创将你的文案改写成一份意思差不多的新文案', 4, 'https://域名/static/wen/twrite.png', '原文案', '粘贴原文案', '改写后的文案', '粘贴原文案，将为你生成一份意思差不多的文案', '0', '3', '进行伪原创，目标或要求：语义更生动，语气更自然'),
(5, '自定义创作', '发挥你的创意生成更多种类的文案', 5, 'https://域名/static/wen/tzdy.png', '主题/要求', '请输入主题或要求', '文案', '帮我写一篇文案\n<br>主题：聚合去水印工具小程序\n<br>种草原因：免费好用，支持多平台\n<br>字数：200\n<br>语气：幽默', '0', '1', '');

SET FOREIGN_KEY_CHECKS = 1;
