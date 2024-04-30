
#### 一、管理服务器
##### 1.启动服务进程
```shell
yasboot process yasom start -c yashandb
yasboot process yasagent start -c yashandb
```

##### 2.启停数据库
```shell
yasboot cluster start -c yashandb
yasboot cluster stop -c yashandb
yasboot cluster restart -c yashandb
```

#### 二、用户管理
##### 1.创建并赋权用户
```
CREATE USER yhzx IDENTIFIED BY yhzx123;
GRANT CREATE SESSION TO yhzx;
GRANT CREATE TABLE TO yhzx;
GRANT ALTER SESSION TO yhzx;
GRANT RESOURCE TO yhzx;

-- 删除用户
DROP USER yhzx CASCADE;
```

#### 三、sql操作
##### 1.表增加自增id
```
# 1.首先创建序列，举例
CREATE SEQUENCE {sequence_name} START WITH 1 INCREMENT BY 1;
# 2.将序列值设为列的默认值：
ALTER TABLE {table_name} MODIFY {column_name} DEFAULT {sequence_name}.NEXTVAL;
```

